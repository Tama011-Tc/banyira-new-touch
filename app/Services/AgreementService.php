<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\AgreementParty;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\Alert;
use App\Support\Money;
use App\Support\Reference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Accepted offers → agreement → signatures → investor funds confirmed →
 * disbursement (maker-checker) → investments and repayment schedules.
 * An offer only becomes an investment at disbursement.
 */
class AgreementService
{
    public function __construct(private RulesService $rules, private LedgerService $ledger, private AuditLogger $audit) {}

    public function createForListing(Listing $listing): Agreement
    {
        if ($listing->agreement()->exists()) {
            return $listing->agreement;
        }
        $listing->loadMissing('application.business.user', 'offers.investor');
        $accepted = $listing->offers->where('status', 'accepted');
        if ($accepted->isEmpty()) {
            throw ValidationException::withMessages(['agreement' => 'There are no accepted offers to form an agreement.']);
        }

        $rules = $this->rules->active();
        $principal = Money::add(...$accepted->pluck('amount')->all());
        $fee = $this->rules->serviceFee($principal, $rules);
        $borrower = $listing->application->business->user;

        $agreement = DB::transaction(function () use ($listing, $accepted, $rules, $principal, $fee, $borrower) {
            $agreement = Agreement::create([
                'reference' => Reference::make('AGR'),
                'listing_id' => $listing->id,
                'borrower_id' => $borrower->id,
                'rule_set_id' => $rules->id,
                'principal_total' => $principal,
                'fee_rate' => $fee['rate'],
                'service_fee' => $fee['fee'],
                'status' => 'awaiting_signatures',
            ]);
            $agreement->parties()->create(['user_id' => $borrower->id, 'role' => 'borrower']);
            foreach ($accepted as $offer) {
                $agreement->parties()->create([
                    'user_id' => $offer->investor_id, 'role' => 'investor', 'investment_offer_id' => $offer->id,
                    'principal' => $offer->amount, 'annual_rate' => $offer->annual_rate, 'term_months' => $offer->term_months,
                ]);
            }

            return $agreement;
        });

        $note = new Alert('Agreement ready to sign', "Agreement {$agreement->reference} is ready for your review and signature.", null, sms: true);
        $borrower->notify(new Alert($note->title, $note->body, '/business/financing', true));
        $accepted->pluck('investor')->unique('id')->each->notify(new Alert($note->title, $note->body, '/investor/agreements', true));

        return $agreement;
    }

    public function sign(Agreement $agreement, User $user): void
    {
        if ($agreement->status !== 'awaiting_signatures') {
            throw ValidationException::withMessages(['agreement' => 'This agreement is not awaiting signatures.']);
        }
        $updated = $agreement->parties()->where('user_id', $user->id)->whereNull('signed_at')->update(['signed_at' => now()]);
        if (! $updated) {
            throw ValidationException::withMessages(['agreement' => 'You have already signed, or you are not a party to this agreement.']);
        }
        $this->audit->log('agreements', "Signed {$agreement->reference}", $agreement, [], $user);

        if (! $agreement->parties()->whereNull('signed_at')->exists()) {
            $agreement->update(['status' => 'awaiting_funding']);
            $agreement->parties()->where('role', 'investor')->with('user')->get()->each(fn ($p) => $p->user->notify(new Alert(
                'Send your investment funds', 'All parties signed '.$agreement->reference.'. Transfer '.Money::format($p->principal).' using the instructions in your dashboard.', '/investor/agreements', true,
            )));
        }
    }

    /** Finance confirms an investor's funds arrived in the client account. */
    public function confirmInvestorFunds(AgreementParty $party, User $finance, string $providerReference): void
    {
        $agreement = $party->agreement;
        if ($agreement->status !== 'awaiting_funding' || $party->role !== 'investor' || $party->funds_confirmed_at) {
            throw ValidationException::withMessages(['funds' => 'These funds cannot be confirmed now.']);
        }

        DB::transaction(function () use ($party, $agreement, $finance, $providerReference) {
            $payment = Payment::create([
                'reference' => Reference::make('PAY'),
                'idempotency_key' => 'fund-'.$party->id,
                'agreement_id' => $agreement->id,
                'payer_id' => $party->user_id,
                'direction' => 'in',
                'purpose' => 'investor_funding',
                'amount' => $party->principal,
                'provider' => config('banyira.payments.driver'),
                'provider_reference' => $providerReference,
                'status' => 'confirmed',
                'is_demo' => config('banyira.payments.driver') === 'demo',
                'confirmed_at' => now(),
                'confirmed_by' => $finance->id,
            ]);
            $party->update(['funds_confirmed_at' => now()]);
            $this->ledger->post("Investor funds received for {$agreement->reference}", [
                ['1020', $party->principal, '0', 'Client funds bank'],
                ['2010', '0', $party->principal, 'Held for disbursement'],
            ], $payment, $finance);

            if (! $agreement->parties()->where('role', 'investor')->whereNull('funds_confirmed_at')->exists()) {
                $agreement->update(['status' => 'awaiting_disbursement']);
            }
        });
    }

    /** Executed only after a second finance officer approves the request. */
    public function disburse(Agreement $agreement, User $maker, User $checker): void
    {
        DB::transaction(function () use ($agreement, $maker, $checker) {
            $agreement = Agreement::whereKey($agreement->id)->lockForUpdate()->firstOrFail();
            if ($agreement->status !== 'awaiting_disbursement') {
                throw ValidationException::withMessages(['agreement' => 'This agreement is not ready for disbursement.']);
            }
            $start = now()->startOfDay();
            foreach ($agreement->parties()->where('role', 'investor')->get() as $party) {
                $inv = $party->investment()->create([
                    'reference' => Reference::make('INV'),
                    'agreement_id' => $agreement->id,
                    'investor_id' => $party->user_id,
                    'principal' => $party->principal,
                    'annual_rate' => $party->annual_rate,
                    'term_months' => $party->term_months,
                    'status' => 'active',
                    'started_on' => $start,
                ]);
                foreach (Money::amortise($party->principal, $party->annual_rate, $party->term_months) as $row) {
                    $inv->schedule()->create([
                        'agreement_id' => $agreement->id,
                        'installment_no' => $row['no'],
                        'due_date' => $start->copy()->addMonthsNoOverflow($row['no']),
                        'principal_due' => $row['principal'],
                        'interest_due' => $row['interest'],
                    ]);
                }
            }

            $net = Money::sub($agreement->principal_total, $agreement->service_fee);
            $payout = Payment::create([
                'reference' => Reference::make('DSB'),
                'idempotency_key' => 'disburse-'.$agreement->id,
                'agreement_id' => $agreement->id,
                'payer_id' => null,
                'direction' => 'out',
                'purpose' => 'disbursement',
                'amount' => $net,
                'provider' => config('banyira.payments.driver'),
                'provider_reference' => 'DSB-'.Str::upper(Str::random(8)),
                'status' => 'confirmed',
                'is_demo' => config('banyira.payments.driver') === 'demo',
                'confirmed_at' => now(),
                'confirmed_by' => $checker->id,
            ]);
            // Fee is deducted at disbursement and moves to Banyira's operating account.
            $this->ledger->post("Disbursement of {$agreement->reference} (fee {$agreement->fee_rate}%)", [
                ['2010', $agreement->principal_total, '0', 'Release investor funds'],
                ['1020', '0', $agreement->principal_total, 'Paid out of client account'],
                ['1010', $agreement->service_fee, '0', 'Service fee to operating account'],
                ['4010', '0', $agreement->service_fee, 'Service fee revenue'],
            ], $payout, $maker, $checker);
            $this->ledger->post("Borrower obligation recognised for {$agreement->reference}", [
                ['1210', $agreement->principal_total, '0'],
                ['2110', '0', $agreement->principal_total],
            ], $agreement, $maker, $checker);

            $agreement->update(['status' => 'active', 'disbursed_at' => now()]);
            $this->audit->log('finance', "Disbursed {$agreement->reference}", $agreement, ['net' => $net, 'fee' => $agreement->service_fee], $checker);
        });

        $agreement->borrower->notify(new Alert('Financing disbursed', "Funds for {$agreement->reference} have been released. Your repayment schedule is in your dashboard.", '/business/repayments', true));
    }
}
