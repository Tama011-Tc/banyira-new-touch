<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\LoanAgreement;
use App\Models\Offer;
use App\Models\OfferVersion;
use App\Models\User;
use App\Support\LoanCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Versioned negotiation. Each proposal is an immutable OfferVersion with a content hash and a fee snapshot. A new
 * proposal supersedes the previous one; an agreement exists only when BOTH parties accept the SAME latest version.
 * Terms can only move inside the limits the credit team approved for the listing.
 */
class OfferService
{
    public function __construct(private readonly MessagingService $messaging, private readonly AgreementService $agreements, private readonly AuditLogger $audit) {}

    public function propose(Conversation $c, User $proposer, array $terms): OfferVersion
    {
        abort_unless($this->messaging->isParticipant($c, $proposer), 403);
        $this->messaging->assertEligibleInvestor($c->investor);
        $listing = $c->listing;
        if ($listing->status !== 'published') {
            throw ValidationException::withMessages(['listing' => 'This opportunity is no longer open.']);
        }
        $this->validateTerms($listing, $terms);

        return DB::transaction(function () use ($c, $proposer, $terms, $listing) {
            Listing::query()->whereKey($listing->id)->lockForUpdate()->first();
            $offer = Offer::firstOrCreate(['conversation_id' => $c->id]);
            if ($offer->status === 'accepted') {
                throw ValidationException::withMessages(['offer' => 'An agreement already exists for this conversation.']);
            }
            $this->assertCapacity($listing, $terms['principal']);
            $this->assertExposure($c->investor, $listing, $terms['principal']);
            $version = ($offer->versions()->max('version') ?? 0) + 1;
            $offer->versions()->where('status', 'proposed')->update(['status' => 'superseded']);
            $offer->forceFill(['status' => 'open'])->save();

            $fees = FeeService::snapshot($terms['principal']);
            $v = OfferVersion::create([
                'offer_id' => $offer->id, 'version' => $version, 'proposed_by' => $proposer->id, 'principal' => $terms['principal'], 'rate_bp' => $terms['rate_bp'],
                'term_months' => $terms['term_months'], 'frequency' => $terms['frequency'], 'interest_method' => $terms['interest_method'], 'grace_instalments' => $terms['grace_instalments'] ?? 0,
                'fees' => $fees, 'content_hash' => self::hash($terms, $fees), 'note' => $terms['note'] ?? null,
                'expires_at' => now()->addDays((int) Settings::get('offers.ttl_days')),
                'investor_accepted_at' => $proposer->id === $c->investor_id ? now() : null,
                'business_accepted_at' => $proposer->id === $c->business->owner_id ? now() : null,
            ]);
            $this->audit->record('offer.proposed', $v, ['version' => $version, 'principal' => $terms['principal'], 'rate_bp' => $terms['rate_bp']], $proposer);

            return $v;
        });
    }

    public function accept(OfferVersion $v, User $actor): ?LoanAgreement
    {
        // Recorded outside the transaction below, which the error would otherwise roll back.
        if ($v->status === 'proposed' && $v->expires_at && $v->expires_at->isPast()) {
            $v->forceFill(['status' => 'expired'])->save();
            throw ValidationException::withMessages(['offer' => 'This proposal has expired. Ask the other party to send a new one.']);
        }

        return DB::transaction(function () use ($v, $actor) {
            $v = OfferVersion::query()->whereKey($v->id)->lockForUpdate()->firstOrFail();
            $offer = $v->offer;
            $c = $offer->conversation;
            abort_unless($this->messaging->isParticipant($c, $actor), 403);
            $latest = $offer->versions()->max('version');
            if ($v->status !== 'proposed' || $v->version !== (int) $latest) {
                throw ValidationException::withMessages(['offer' => 'Only the latest proposal can be accepted.']);
            }
            if ($v->content_hash !== self::hash($this->termsOf($v), $v->fees)) {
                throw ValidationException::withMessages(['offer' => 'The offer was altered after it was proposed and cannot be accepted.']);
            }
            $listing = Listing::query()->whereKey($c->listing_id)->lockForUpdate()->first();
            if ($listing->status !== 'published') {
                throw ValidationException::withMessages(['listing' => 'This opportunity is no longer open.']);
            }
            if ($actor->id === $c->investor_id) {
                $this->messaging->assertEligibleInvestor($actor);
                $v->investor_accepted_at ??= now();
            } else {
                $v->business_accepted_at ??= now();
            }
            $this->audit->record('offer.accepted', $v, ['by' => $actor->id === $c->investor_id ? 'investor' : 'business'], $actor);
            if (! ($v->investor_accepted_at && $v->business_accepted_at)) {
                $v->save();

                return null;
            }
            $this->assertCapacity($listing, $v->principal);
            $this->assertExposure($c->investor, $listing, $v->principal);
            $v->forceFill(['status' => 'accepted'])->save();
            $offer->forceFill(['status' => 'accepted'])->save();

            return $this->agreements->createFromOffer($v);
        });
    }

    /** The proposer takes back an unanswered proposal. Once both sides accepted, the agreement is binding. */
    public function withdraw(OfferVersion $v, User $actor): void
    {
        $c = $v->offer->conversation;
        abort_unless($this->messaging->isParticipant($c, $actor) && $v->proposed_by === $actor->id, 403);
        $v = OfferVersion::query()->whereKey($v->id)->lockForUpdate()->firstOrFail();
        if ($v->status !== 'proposed') {
            throw ValidationException::withMessages(['offer' => 'Only an unanswered proposal can be withdrawn.']);
        }
        $v->forceFill(['status' => 'withdrawn'])->save();
        $this->audit->record('offer.withdrawn', $v, [], $actor);
    }

    public function decline(OfferVersion $v, User $actor): void
    {
        $c = $v->offer->conversation;
        abort_unless($this->messaging->isParticipant($c, $actor), 403);
        if ($v->status !== 'proposed') {
            throw ValidationException::withMessages(['offer' => 'This proposal is no longer open.']);
        }
        $v->forceFill(['status' => 'declined'])->save();
        $v->offer->forceFill(['status' => 'open'])->save();
        $this->audit->record('offer.declined', $v, [], $actor);
    }

    private function validateTerms(Listing $l, array &$t): void
    {
        $t['grace_instalments'] = (int) ($t['grace_instalments'] ?? 0);
        $err = [];
        if ($t['principal'] < $l->min_investment || $t['principal'] > $l->max_investment) {
            $err['principal'] = 'The amount must be between '.Money::format($l->min_investment).' and '.Money::format($l->max_investment).'.';
        }
        if ($t['rate_bp'] < 0 || $t['rate_bp'] > $l->max_rate_bp) {
            $err['rate'] = 'The interest rate cannot exceed the approved ceiling of '.number_format($l->max_rate_bp / 100, 2).'% a year.';
        }
        if ($t['term_months'] < 1 || $t['term_months'] > $l->term_months) {
            $err['term_months'] = "The term cannot exceed the approved {$l->term_months} months.";
        }
        if (! in_array($t['frequency'], ['weekly', 'monthly'], true) || ! in_array($t['interest_method'], ['flat', 'reducing'], true)) {
            $err['frequency'] = 'Unsupported repayment frequency or interest method.';
        }
        if ($t['grace_instalments'] < 0 || $t['grace_instalments'] > (int) Settings::get('loan.max_grace_instalments')) {
            $err['grace'] = 'The grace period is outside the permitted range.';
        }
        if ($err) {
            throw ValidationException::withMessages($err);
        }
        // Prove the schedule can be built before anyone signs.
        LoanCalculator::schedule($t['principal'], $t['rate_bp'], $t['term_months'], $t['frequency'], $t['interest_method'], $t['grace_instalments'], now()->toDateString());
    }

    /** Investor caps: a personal per-listing limit on their profile, and an optional platform-wide total. */
    private function assertExposure(User $investor, Listing $listing, int $principal): void
    {
        $live = LoanAgreement::where('investor_id', $investor->id)->where('status', '!=', 'cancelled');
        $perListing = $investor->investorProfile?->max_per_listing;
        if ($perListing !== null && (clone $live)->where('listing_id', $listing->id)->sum('principal') + $principal > $perListing) {
            throw ValidationException::withMessages(['principal' => 'This would exceed your personal limit of '.Money::format($perListing).' for one business.']);
        }
        $total = Settings::get('investor.max_total_commitment');
        if ($total !== null && (clone $live)->sum('principal') + $principal > $total) {
            throw ValidationException::withMessages(['principal' => 'This would exceed the platform limit of '.Money::format((int) $total).' in total commitments per investor.']);
        }
    }

    private function assertCapacity(Listing $l, int $principal): void
    {
        $committed = (int) LoanAgreement::where('listing_id', $l->id)->where('status', '!=', 'cancelled')->sum('principal');
        if ($committed + $principal > $l->funding_target) {
            throw ValidationException::withMessages(['principal' => 'Only '.Money::format(max(0, $l->funding_target - $committed)).' is still available on this opportunity.']);
        }
    }

    public static function hash(array $t, array $fees): string
    {
        ksort($fees);   // MySQL JSON columns do not preserve key order, so hash a canonical order
        return hash('sha256', json_encode([(int) $t['principal'], (int) $t['rate_bp'], (int) $t['term_months'], $t['frequency'], $t['interest_method'], (int) ($t['grace_instalments'] ?? 0), $fees]));
    }

    public function termsOf(OfferVersion $v): array
    {
        return ['principal' => $v->principal, 'rate_bp' => $v->rate_bp, 'term_months' => $v->term_months, 'frequency' => $v->frequency, 'interest_method' => $v->interest_method, 'grace_instalments' => $v->grace_instalments];
    }
}
