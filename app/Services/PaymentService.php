<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\Disbursement;
use App\Models\InvestmentCommitment;
use App\Models\LoanAgreement;
use App\Models\PaymentAttempt;
use App\Models\PlatformFee;
use App\Models\Repayment;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly SandboxProvider $provider, private readonly LedgerService $ledger, private readonly RepaymentService $repayments,
        private readonly AuditLogger $audit, private readonly ApplicationWorkflow $workflow,
    ) {}

    /** Amount the investor must pay to fund an agreement: principal + disclosed investor fee (+ tax). */
    public function fundingAmount(LoanAgreement $a): int
    {
        return FeeService::onPrincipal($a->fees, $a->principal)['investor_pays'];
    }

    public function initiate(User $user, LoanAgreement $a, string $purpose, int $amount, string $idempotencyKey): PaymentAttempt
    {
        Platform::assertMayMoveMoney();
        if ($existing = PaymentAttempt::where('idempotency_key', $idempotencyKey)->first()) {
            abort_unless($existing->user_id === $user->id, 403);

            return $existing;
        }
        if ($purpose === 'funding') {
            abort_unless($a->investor_id === $user->id, 403);
            if ($a->status !== 'pending_funding') {
                throw ValidationException::withMessages(['payment' => 'This agreement is not awaiting funding.']);
            }
            if ($amount !== $this->fundingAmount($a)) {
                throw ValidationException::withMessages(['amount' => 'The funding amount must equal the agreed principal plus disclosed fees.']);
            }
            if (PaymentAttempt::where('agreement_id', $a->id)->where('purpose', 'funding')->whereIn('status', ['initiated', 'succeeded'])->exists()) {
                throw ValidationException::withMessages(['payment' => 'A funding payment is already in progress.']);
            }
        } else {
            abort_unless($a->business->owner_id === $user->id, 403);
            if (! in_array($a->status, ['active', 'overdue', 'restructured'], true) || $amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'This loan cannot accept a repayment right now.']);
            }
        }
        try {
            $ref = $this->provider->create($amount);

            return PaymentAttempt::create(['user_id' => $user->id, 'agreement_id' => $a->id, 'purpose' => $purpose, 'amount' => $amount, 'idempotency_key' => $idempotencyKey, 'provider' => SandboxProvider::NAME, 'provider_ref' => $ref, 'simulated' => true]);
        } catch (UniqueConstraintViolationException) {
            return PaymentAttempt::where('idempotency_key', $idempotencyKey)->firstOrFail();
        }
    }

    /**
     * Provider callback. Authenticity comes from the HMAC signature; the OUTCOME comes from asking the provider
     * (verify), never from the request body. Replays are recorded and ignored.
     *
     * @return string processed | duplicate | rejected | unknown_payment | failed_recorded
     */
    public function handleWebhook(string $body, ?string $signature): string
    {
        $valid = SandboxProvider::validSignature($body, $signature);
        $payload = json_decode($body, true) ?: [];
        $eventId = (string) ($payload['event_id'] ?? '');
        if (! $valid || $eventId === '') {
            $this->logEvent('bad-'.substr(hash('sha256', $body.'|'.$signature.'|'.microtime(true)), 0, 24), $payload, false, 'rejected');

            return 'rejected';
        }
        try {
            $event = WebhookEvent::create(['provider' => SandboxProvider::NAME, 'event_id' => $eventId, 'signature_valid' => true, 'payload' => $payload, 'created_at' => now()]);
        } catch (UniqueConstraintViolationException) {
            return 'duplicate';
        }
        $outcome = DB::transaction(function () use ($payload) {
            $attempt = PaymentAttempt::where('provider_ref', $payload['ref'] ?? '')->lockForUpdate()->first();
            if (! $attempt) {
                return 'unknown_payment';
            }
            if ($attempt->status !== 'initiated') {
                return 'duplicate';
            }
            $truth = $this->provider->verify($attempt->provider_ref);
            if (! $truth || $truth['amount'] !== (int) $attempt->amount) {
                $attempt->forceFill(['status' => 'failed', 'failure_reason' => 'Provider amount mismatch'])->save();

                return 'failed_recorded';
            }
            if ($truth['status'] === 'failed') {
                $attempt->forceFill(['status' => 'failed', 'failure_reason' => 'Declined by provider'])->save();
                $this->audit->record('payment.failed', $attempt, [], null);

                return 'failed_recorded';
            }
            if ($truth['status'] !== 'succeeded') {
                return 'unknown_payment';
            }
            $attempt->forceFill(['status' => 'succeeded'])->save();
            $attempt->purpose === 'funding' ? $this->onFunded($attempt) : $this->onRepaid($attempt);

            return 'processed';
        });
        $event->forceFill(['outcome' => $outcome])->save();

        return $outcome;
    }

    private function logEvent(string $id, array $payload, bool $valid, string $outcome): void
    {
        WebhookEvent::firstOrCreate(['provider' => SandboxProvider::NAME, 'event_id' => $id], ['signature_valid' => $valid, 'payload' => $payload ?: ['raw' => 'unparseable'], 'outcome' => $outcome, 'created_at' => now()]);
    }

    private function onFunded(PaymentAttempt $attempt): void
    {
        $a = LoanAgreement::query()->whereKey($attempt->agreement_id)->lockForUpdate()->firstOrFail();
        if ($a->status !== 'pending_funding') {
            return;
        }
        $f = FeeService::onPrincipal($a->fees, $a->principal);
        $processing = FeeService::processing($a->fees, $attempt->amount);
        $this->ledger->post('payment_attempt', $attempt->id, array_values(array_filter([
            [LedgerService::CLEARING, 'debit', $attempt->amount, 'Investor funding (simulated)'],
            [LedgerService::INVESTOR_FUNDS, 'credit', $a->principal],
            [LedgerService::FEE_REVENUE, 'credit', $f['investor_service']],
            [LedgerService::TAX_PAYABLE, 'credit', $f['investor_service_tax']],
        ], fn ($l) => $l[2] > 0)), 'Funding received');
        if ($processing > 0) {
            $this->ledger->post('payment_attempt', $attempt->id, [[LedgerService::PROVIDER_FEES, 'debit', $processing, 'Processing fee'], [LedgerService::CLEARING, 'credit', $processing]], 'Provider processing fee');
            PlatformFee::create(['agreement_id' => $a->id, 'fee_type' => 'processing', 'payer' => 'platform', 'basis_amount' => $attempt->amount, 'rate_bp' => $a->fees['processing_bp'], 'amount' => $processing, 'net' => $processing, 'status' => 'collected', 'reconciliation_ref' => $attempt->provider_ref]);
        }
        $f['investor_service'] > 0 && PlatformFee::create(['agreement_id' => $a->id, 'fee_type' => 'investor_service', 'payer' => 'investor', 'basis_amount' => $a->principal, 'rate_bp' => $a->fees['investor_service_bp'], 'amount' => $f['investor_service'], 'tax' => $f['investor_service_tax'], 'net' => $f['investor_service'], 'status' => 'collected', 'reconciliation_ref' => $attempt->provider_ref]);
        InvestmentCommitment::where('agreement_id', $a->id)->update(['status' => 'funded']);
        $a->forceFill(['status' => 'funded', 'funded_at' => now()])->save();
        Disbursement::create(['agreement_id' => $a->id, 'gross' => $a->principal, 'fees' => $f['borrower_total'], 'net' => $f['net_to_business'], 'status' => 'pending_approval', 'created_at' => now()]);
        $this->audit->record('agreement.funded', $a, ['attempt' => $attempt->id, 'simulated' => true], null);
        $this->refreshListingProgress($a);
    }

    /** Releasing money to the business is a finance decision, separate from the investor paying in. */
    public function approveDisbursement(Disbursement $d, User $approver): Disbursement
    {
        abort_unless($approver->role->can('finance.approve'), 403);
        Platform::assertMayMoveMoney();

        return DB::transaction(function () use ($d, $approver) {
            $d = Disbursement::query()->whereKey($d->id)->lockForUpdate()->firstOrFail();
            if ($d->status !== 'pending_approval') {
                throw ValidationException::withMessages(['disbursement' => 'This disbursement is not awaiting approval.']);
            }
            $a = LoanAgreement::query()->whereKey($d->agreement_id)->lockForUpdate()->firstOrFail();
            $campaign = $this->campaignStatus($a->listing);
            if (! $campaign['ready']) {
                throw ValidationException::withMessages(['campaign' => "The campaign is {$campaign['funded_pct']}% funded; the business is paid only once it reaches {$campaign['min_pct']}%."]);
            }
            $f = FeeService::onPrincipal($a->fees, $a->principal);
            $this->ledger->post('disbursement', $d->id, array_values(array_filter([
                [LedgerService::INVESTOR_FUNDS, 'debit', $a->principal, 'Release to business (simulated)'],
                [LedgerService::BUSINESS_DISBURSED, 'credit', $f['net_to_business']],
                [LedgerService::FEE_REVENUE, 'credit', $f['origination']],
                [LedgerService::TAX_PAYABLE, 'credit', $f['origination_tax']],
            ], fn ($l) => $l[2] > 0)), 'Disbursement');
            $f['origination'] > 0 && PlatformFee::create(['agreement_id' => $a->id, 'fee_type' => 'origination', 'payer' => 'borrower', 'basis_amount' => $a->principal, 'rate_bp' => $a->fees['origination_bp'], 'amount' => $f['origination'], 'tax' => $f['origination_tax'], 'net' => $f['origination'], 'status' => 'collected']);
            $d->forceFill(['status' => 'simulated', 'approved_by' => $approver->id])->save();
            $a->forceFill(['status' => 'active', 'disbursed_at' => now()])->save();
            $this->audit->record('disbursement.approved', $d, ['agreement' => $a->id, 'net' => $d->net, 'simulated' => true], $approver);
            $this->refreshApplication($a);

            return $d;
        });
    }

    private function onRepaid(PaymentAttempt $attempt): void
    {
        $a = LoanAgreement::query()->whereKey($attempt->agreement_id)->lockForUpdate()->firstOrFail();
        $this->repayments->apply($a, $attempt);
    }

    /** How much of the target is actually funded, and whether the business may be paid yet. */
    public function campaignStatus(\App\Models\Listing $listing): array
    {
        $funded = (int) LoanAgreement::where('listing_id', $listing->id)->whereNotNull('funded_at')->where('status', '!=', 'cancelled')->sum('principal');
        $pct = $listing->funding_target > 0 ? (int) floor($funded * 100 / $listing->funding_target) : 0;
        $min = (int) Settings::get('campaign.min_funded_pct');

        return ['funded_pct' => $pct, 'min_pct' => $min, 'ready' => $pct >= $min];
    }

    private function refreshListingProgress(LoanAgreement $a): void
    {
        $listing = $a->listing;
        $funded = (int) LoanAgreement::where('listing_id', $listing->id)->whereIn('status', ['funded', 'active', 'overdue', 'completed', 'restructured'])->sum('principal');
        if ($funded >= $listing->funding_target && $listing->status === 'published') {
            $listing->forceFill(['status' => 'funded'])->save();
            if ($listing->application->status === 'funding') {
                $this->workflow->transition($listing->application, 'funded', null, 'Funding target met');
            }
        }
    }

    private function refreshApplication(LoanAgreement $a): void
    {
        $app = $a->application->fresh();
        if ($app->status === 'funded' && ! LoanAgreement::where('application_id', $app->id)->where('status', 'funded')->exists()) {
            $this->workflow->transition($app, 'repayment_active', null, 'All funds disbursed');
        }
    }

    /**
     * Reverse a succeeded payment. Needs a second person's approval (ApprovalRequest) and posts reversing ledger
     * entries; nothing is deleted. Funding reversals are only possible before disbursement.
     */
    public function reverse(PaymentAttempt $attempt, ApprovalRequest $approval, User $actor): void
    {
        abort_unless($actor->role->can('finance.approve'), 403);
        if ($approval->status !== 'approved' || $approval->type !== 'reversal' || ($approval->payload['attempt_id'] ?? null) !== $attempt->id) {
            throw ValidationException::withMessages(['approval' => 'A matching, approved second-person approval is required.']);
        }
        if ($approval->requested_by === $approval->decided_by) {
            throw ValidationException::withMessages(['approval' => 'Requester and approver must be different people.']);
        }
        DB::transaction(function () use ($attempt, $approval, $actor) {
            $attempt = PaymentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($attempt->status !== 'succeeded') {
                throw ValidationException::withMessages(['payment' => 'Only a succeeded payment can be reversed.']);
            }
            $a = LoanAgreement::query()->whereKey($attempt->agreement_id)->lockForUpdate()->firstOrFail();
            if ($attempt->purpose === 'funding') {
                $d = Disbursement::where('agreement_id', $a->id)->first();
                if ($d && $d->status === 'simulated') {
                    throw ValidationException::withMessages(['payment' => 'Funds were already released to the business; reverse through a recovery process instead.']);
                }
                $d?->forceFill(['status' => 'cancelled'])->save();
                $a->forceFill(['status' => 'cancelled'])->save();
                InvestmentCommitment::where('agreement_id', $a->id)->update(['status' => 'cancelled']);
                PlatformFee::where('agreement_id', $a->id)->where('status', 'collected')->update(['status' => 'refunded']);
            } else {
                $this->repayments->reverse(Repayment::where('payment_attempt_id', $attempt->id)->firstOrFail());
            }
            $groups = \App\Models\LedgerEntry::where('ref_type', 'payment_attempt')->where('ref_id', $attempt->id)->whereNull('reverses_group')->distinct()->pluck('entry_group');
            foreach ($groups as $g) {
                $this->ledger->reverse($g, $approval->reason);
            }
            $attempt->forceFill(['status' => 'reversed'])->save();
            $this->audit->record('payment.reversed', $attempt, ['approval' => $approval->id], $actor);
        });
    }
}
