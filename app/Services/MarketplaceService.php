<?php

namespace App\Services;

use App\Models\InvestmentOffer;
use App\Models\LoanApplication;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\Alert;
use App\Support\Money;
use App\Support\Reference;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Peer-to-peer marketplace: listings, investor bids and business decisions.
 * Acceptance locks the listing row so concurrent acceptances can never
 * commit more than the amount requested.
 */
class MarketplaceService
{
    public function __construct(
        private ApplicationWorkflow $workflow,
        private RulesService $rules,
        private AgreementService $agreements,
        private AuditLogger $audit,
    ) {}

    public function publish(LoanApplication $app, User $by, array $data): Listing
    {
        if ($app->status !== 'approved') {
            throw ValidationException::withMessages(['listing' => 'Only approved applications can be published.']);
        }
        $app->loadMissing('business');
        $ceiling = $this->rules->active()->interest_ceiling;
        if (Money::cmp($data['indicative_rate'], $ceiling) > 0) {
            throw ValidationException::withMessages(['indicative_rate' => "The indicative rate cannot exceed the {$ceiling}% ceiling."]);
        }

        return DB::transaction(function () use ($app, $by, $data) {
            $listing = $app->listing()->create([
                'reference' => Reference::make('LST'),
                'public_name' => $data['public_name'],
                'industry' => $app->business->industry,
                'region' => $app->business->region ?? 'Malawi',
                'summary' => $data['summary'],
                'purpose' => $data['purpose'],
                'amount_requested' => $app->amount,
                'risk_class' => $app->risk_class,
                'indicative_rate' => $data['indicative_rate'],
                'term_months' => $app->term_months,
                'status' => 'open',
                'published_by' => $by->id,
                'published_at' => now(),
                'closes_at' => now()->addDays(config('banyira.marketplace.listing_days')),
            ]);
            $this->workflow->transition($app, 'listed', $by, 'Published as '.$listing->reference);

            return $listing;
        });
    }

    public function makeOffer(Listing $listing, User $investor, array $data): InvestmentOffer
    {
        $profile = $investor->investorProfile;
        if ($profile?->verification_status !== 'verified') {
            throw ValidationException::withMessages(['amount' => 'Complete identity verification before making offers.']);
        }
        if (! $listing->isOpen()) {
            throw ValidationException::withMessages(['amount' => 'This listing is no longer accepting offers.']);
        }
        if ($listing->offers()->where('investor_id', $investor->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['amount' => 'You already have a pending offer on this business. Withdraw it to make a new one.']);
        }

        $rules = $this->rules->active();
        $amount = Money::of($data['amount']);
        $errors = [];
        if (Money::cmp($amount, config('banyira.marketplace.min_offer')) < 0) {
            $errors['amount'] = 'The minimum offer is '.Money::format(config('banyira.marketplace.min_offer')).'.';
        } elseif (Money::cmp($amount, $listing->remaining()) > 0) {
            $errors['amount'] = 'Only '.Money::format($listing->remaining()).' remains to be funded.';
        }
        if (Money::cmp($data['annual_rate'], '0') <= 0 || Money::cmp($data['annual_rate'], $rules->interest_ceiling) > 0) {
            $errors['annual_rate'] = "Enter a rate above 0% and no higher than {$rules->interest_ceiling}% a year.";
        }
        $band = $rules->band($listing->risk_class);
        if ($band?->max_months && $data['term_months'] > $band->max_months) {
            $errors['term_months'] = "Class {$listing->risk_class} financing is limited to {$band->max_months} months.";
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $offer = DB::transaction(function () use ($listing, $investor, $data, $amount) {
            $offer = new InvestmentOffer([
                'reference' => Reference::make('OFR'),
                'listing_id' => $listing->id,
                'investor_id' => $investor->id,
                'amount' => $amount,
                'annual_rate' => $data['annual_rate'],
                'term_months' => $data['term_months'],
                'conditions' => $data['conditions'] ?? null,
                'expires_at' => now()->addDays(config('banyira.marketplace.offer_expiry_days')),
            ]);
            $offer->status = 'pending';
            $offer->save();
            $offer->events()->create(['to_status' => 'pending', 'user_id' => $investor->id]);

            return $offer;
        });

        $listing->application->business->user->notify(new Alert('New investor offer', Money::format($amount)." at {$data['annual_rate']}% for {$data['term_months']} months.", '/business/offers', sms: true));

        return $offer;
    }

    public function withdraw(InvestmentOffer $offer, User $investor): void
    {
        if ($offer->investor_id !== $investor->id || $offer->status !== 'pending') {
            throw ValidationException::withMessages(['offer' => 'Only your own pending offers can be withdrawn.']);
        }
        $this->setStatus($offer, 'withdrawn', $investor);
    }

    public function accept(InvestmentOffer $offer, User $owner): InvestmentOffer
    {
        $listing = DB::transaction(function () use ($offer, $owner) {
            $listing = Listing::whereKey($offer->listing_id)->lockForUpdate()->firstOrFail();
            $offer = InvestmentOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            $this->assertOwner($listing, $owner);

            if ($offer->status !== 'pending' || $offer->expires_at->isPast()) {
                throw ValidationException::withMessages(['offer' => 'This offer is no longer available.']);
            }
            if ($listing->status !== 'open') {
                throw ValidationException::withMessages(['offer' => 'Funding for this listing is closed.']);
            }
            if (Money::cmp($offer->amount, $listing->remaining()) > 0) {
                throw ValidationException::withMessages(['offer' => 'Accepting this offer would exceed the amount you requested. Only '.Money::format($listing->remaining()).' remains.']);
            }

            $listing->amount_accepted = Money::add($listing->amount_accepted, $offer->amount);
            $full = Money::isZero($listing->remaining());
            if ($full) {
                $listing->status = 'fully_funded';
            }
            $listing->save();
            $this->setStatus($offer, 'accepted', $owner);
            $this->audit->log('marketplace', "Accepted offer {$offer->reference} on {$listing->reference}", $offer, ['amount' => $offer->amount], $owner);

            if ($full) {
                $this->expirePending($listing, $owner);
                $this->workflow->transition($listing->application, 'fully_funded', $owner, 'All requested funding accepted');
            }

            return $listing;
        });

        $offer->investor->notify(new Alert('Offer accepted', "Your offer {$offer->reference} was accepted. An agreement will follow.", '/investor/offers', sms: true));
        if ($listing->status === 'fully_funded') {
            $this->agreements->createForListing($listing);
        }

        return $offer->refresh();
    }

    public function reject(InvestmentOffer $offer, User $owner): void
    {
        $this->assertOwner($offer->listing, $owner);
        if ($offer->status !== 'pending') {
            throw ValidationException::withMessages(['offer' => 'Only pending offers can be rejected.']);
        }
        $this->setStatus($offer, 'rejected', $owner);
        $offer->investor->notify(new Alert('Offer not accepted', "Your offer {$offer->reference} was not accepted by the business.", '/investor/offers', sms: true));
    }

    /** The business stops fundraising and proceeds with what it has accepted. */
    public function closeFunding(Listing $listing, User $owner): void
    {
        $this->assertOwner($listing, $owner);
        DB::transaction(function () use ($listing, $owner) {
            $listing = Listing::whereKey($listing->id)->lockForUpdate()->firstOrFail();
            if ($listing->status !== 'open') {
                throw ValidationException::withMessages(['listing' => 'Funding is already closed.']);
            }
            $listing->update(['status' => Money::isZero($listing->amount_accepted) ? 'withdrawn' : 'closed']);
            $this->expirePending($listing, $owner);
            $this->workflow->transition($listing->application, 'closed', $owner, Money::isZero($listing->amount_accepted) ? 'Listing withdrawn' : 'Funding closed with '.Money::format($listing->amount_accepted));
        });
        if ($listing->refresh()->status === 'closed') {
            $this->agreements->createForListing($listing);
        }
    }

    /** Expire pending offers past their expiry date. Scheduled hourly. */
    public function expireStale(): int
    {
        $n = 0;
        InvestmentOffer::where('status', 'pending')->where('expires_at', '<', now())->each(function ($o) use (&$n) {
            $this->setStatus($o, 'expired', null);
            $n++;
        });

        return $n;
    }

    private function expirePending(Listing $listing, ?User $by): void
    {
        $listing->offers()->where('status', 'pending')->get()->each(fn ($o) => $this->setStatus($o, 'expired', $by));
    }

    private function setStatus(InvestmentOffer $offer, string $to, ?User $by): void
    {
        $from = $offer->status;
        $offer->status = $to;
        $offer->decided_at = now();
        $offer->save();
        $offer->events()->create(['from_status' => $from, 'to_status' => $to, 'user_id' => $by?->id]);
    }

    private function assertOwner(Listing $listing, User $owner): void
    {
        if ($listing->application()->value('business_profile_id') !== $owner->businessProfile?->id) {
            throw ValidationException::withMessages(['offer' => 'You can only manage offers on your own listing.']);
        }
    }
}
