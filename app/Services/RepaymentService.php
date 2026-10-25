<?php

namespace App\Services;

use App\Models\LoanAgreement;
use App\Models\PaymentAttempt;
use App\Models\PlatformFee;
use App\Models\Repayment;
use App\Models\RepaymentAllocation;
use App\Models\RepaymentSchedule;

class RepaymentService
{
    public function __construct(private readonly LedgerService $ledger, private readonly AuditLogger $audit, private readonly ApplicationWorkflow $workflow) {}

    /**
     * Allocate a confirmed payment: oldest instalment first, interest before principal, any excess held as
     * `unapplied` on the agreement and used against later instalments. Exactly once per payment (unique constraint).
     */
    public function apply(LoanAgreement $a, PaymentAttempt $attempt): Repayment
    {
        $priorUnapplied = (int) $a->unapplied;
        $available = $attempt->amount + $priorUnapplied;
        $appliedInterest = $appliedPrincipal = 0;
        $rows = RepaymentSchedule::where('agreement_id', $a->id)->where('status', '!=', 'paid')->orderBy('number')->lockForUpdate()->get();

        $r = Repayment::create(['agreement_id' => $a->id, 'payment_attempt_id' => $attempt->id, 'amount' => $attempt->amount, 'received_at' => now()]);
        foreach ($rows as $row) {
            if ($available <= 0) {
                break;
            }
            $i = min($available, $row->interest_due - $row->interest_paid);
            $available -= $i;
            $p = min($available, $row->principal_due - $row->principal_paid);
            $available -= $p;
            if ($i + $p === 0) {
                continue;
            }
            $row->interest_paid += $i;
            $row->principal_paid += $p;
            $row->status = ($row->interest_paid >= $row->interest_due && $row->principal_paid >= $row->principal_due) ? 'paid' : 'partial';
            $row->save();
            RepaymentAllocation::create(['repayment_id' => $r->id, 'schedule_id' => $row->id, 'interest' => $i, 'principal' => $p]);
            $appliedInterest += $i;
            $appliedPrincipal += $p;
        }
        $newUnapplied = $available;
        $r->forceFill(['applied_interest' => $appliedInterest, 'applied_principal' => $appliedPrincipal, 'unapplied' => $newUnapplied])->save();
        $a->forceFill(['unapplied' => $newUnapplied])->save();

        // Cash in = what was applied to instalments + the change in held excess. Always balances: X - A = U1 - U0.
        $applied = $appliedInterest + $appliedPrincipal;
        $delta = $newUnapplied - $priorUnapplied;
        $lines = [[LedgerService::CLEARING, 'debit', $attempt->amount, 'Borrower repayment (simulated)']];
        if ($applied > 0) {
            $lines[] = [LedgerService::REPAYMENTS_PAYABLE, 'credit', $applied];
        }
        if ($delta !== 0) {
            $lines[] = [LedgerService::UNAPPLIED, $delta > 0 ? 'credit' : 'debit', abs($delta)];
        }
        $this->ledger->post('payment_attempt', $attempt->id, $lines, 'Repayment received');
        $processing = FeeService::processing($a->fees, $attempt->amount);
        if ($processing > 0) {
            $this->ledger->post('payment_attempt', $attempt->id, [[LedgerService::PROVIDER_FEES, 'debit', $processing], [LedgerService::CLEARING, 'credit', $processing]], 'Provider processing fee');
            PlatformFee::create(['agreement_id' => $a->id, 'fee_type' => 'processing', 'payer' => 'platform', 'basis_amount' => $attempt->amount, 'rate_bp' => $a->fees['processing_bp'], 'amount' => $processing, 'net' => $processing, 'status' => 'collected', 'reconciliation_ref' => $attempt->provider_ref]);
        }
        $this->audit->record('repayment.applied', $r, ['agreement' => $a->id, 'interest' => $appliedInterest, 'principal' => $appliedPrincipal, 'unapplied' => $newUnapplied, 'simulated' => true], null);
        $this->refreshStatus($a);

        return $r;
    }

    /** Undo a repayment's effect on the schedule. The Repayment row stays, marked reversed. */
    public function reverse(Repayment $r): void
    {
        foreach ($r->allocations as $al) {
            $row = RepaymentSchedule::query()->whereKey($al->schedule_id)->lockForUpdate()->first();
            $row->interest_paid -= $al->interest;
            $row->principal_paid -= $al->principal;
            $row->status = ($row->interest_paid + $row->principal_paid) === 0 ? 'scheduled' : 'partial';
            $row->save();
        }
        $r->forceFill(['reversed_at' => now()])->save();
        $a = LoanAgreement::query()->whereKey($r->agreement_id)->lockForUpdate()->first();
        $a->forceFill(['unapplied' => max(0, $a->unapplied - $r->unapplied)])->save();
        $this->refreshStatus($a);
    }

    /** Derive agreement status from the schedule: completed, overdue (a past-due unpaid instalment) or active. */
    public function refreshStatus(LoanAgreement $a): LoanAgreement
    {
        if (! in_array($a->status, ['active', 'overdue', 'restructured', 'completed'], true)) {
            return $a;
        }
        $rows = RepaymentSchedule::where('agreement_id', $a->id)->get();
        $overdue = $rows->filter(fn ($r) => $r->status !== 'paid' && $r->due_on->isPast() && ! $r->due_on->isToday());
        foreach ($rows as $r) {
            $should = $r->status === 'paid' ? 'paid' : ($overdue->contains('id', $r->id) ? 'overdue' : ($r->principal_paid + $r->interest_paid > 0 ? 'partial' : 'scheduled'));
            if ($r->status !== $should) {
                $r->forceFill(['status' => $should])->save();
            }
        }
        $new = $rows->every(fn ($r) => $r->fresh()->status === 'paid') ? 'completed' : ($overdue->isNotEmpty() ? 'overdue' : ($a->status === 'restructured' ? 'restructured' : 'active'));
        if ($new !== $a->status && $a->status !== 'defaulted') {
            $a->forceFill(['status' => $new])->save();
            $app = $a->application->fresh();
            $siblings = LoanAgreement::where('application_id', $app->id)->get();
            $appState = $siblings->every(fn ($s) => $s->status === 'completed') ? 'completed' : ($siblings->contains(fn ($s) => $s->status === 'overdue') ? 'overdue' : 'repayment_active');
            if ($appState !== $app->status && $this->workflowAllows($app, $appState)) {
                $this->workflow->transition($app, $appState, null, 'Derived from repayment status');
            }
        }

        return $a;
    }

    private function workflowAllows($app, string $to): bool
    {
        return $this->workflow->canTransition($app, $to);
    }

    /** Contractual definition only: N days past the oldest unpaid due date, and a named person decides. */
    public function markDefault(LoanAgreement $a, \App\Models\User $actor, string $reason): void
    {
        abort_unless($actor->role->can('credit.approve'), 403);
        $oldest = RepaymentSchedule::where('agreement_id', $a->id)->where('status', '!=', 'paid')->orderBy('due_on')->first();
        $days = $oldest ? (int) $oldest->due_on->diffInDays(now(), false) : 0;
        $min = (int) Settings::get('loan.default_days');
        if ($days < $min || mb_strlen(trim($reason)) < 10) {
            throw \Illuminate\Validation\ValidationException::withMessages(['default' => "A default needs at least {$min} days past due and a written reason (currently {$days} days)."]);
        }
        $a->forceFill(['status' => 'defaulted'])->save();
        $app = $a->application->fresh();
        if ($this->workflow->canTransition($app, 'defaulted')) {
            $this->workflow->transition($app, 'defaulted', $actor, $reason);
        }
        $this->audit->record('agreement.defaulted', $a, ['days_past_due' => $days, 'reason' => $reason], $actor);
    }
}
