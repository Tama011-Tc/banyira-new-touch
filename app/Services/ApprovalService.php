<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\ApprovalRequest;
use App\Models\Expense;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Maker-checker for sensitive financial operations. The person who prepares
 * an operation can never approve it.
 */
class ApprovalService
{
    public function __construct(private AgreementService $agreements, private LedgerService $ledger, private AuditLogger $audit) {}

    public function request(string $type, Model $subject, string $summary, ?string $amount, User $maker, array $payload = []): ApprovalRequest
    {
        if (ApprovalRequest::where('type', $type)->where('subject_type', $subject->getMorphClass())->where('subject_id', $subject->getKey())->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['approval' => 'An approval request for this item is already pending.']);
        }
        $req = ApprovalRequest::create([
            'type' => $type, 'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(),
            'summary' => $summary, 'amount' => $amount, 'payload' => $payload, 'requested_by' => $maker->id, 'status' => 'pending',
        ]);
        $this->audit->log('approvals', "Requested approval: {$summary}", $req, [], $maker);

        return $req;
    }

    public function decide(ApprovalRequest $req, bool $approve, User $checker, ?string $note = null): void
    {
        if ($req->status !== 'pending') {
            throw ValidationException::withMessages(['approval' => 'This request has already been decided.']);
        }
        if ($req->requested_by === $checker->id) {
            throw ValidationException::withMessages(['approval' => 'You cannot approve a request you prepared. Another authorised officer must approve it.']);
        }

        DB::transaction(function () use ($req, $approve, $checker, $note) {
            $req->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $checker->id, 'decided_at' => now(), 'decision_note' => $note]);
            $maker = $req->requester;
            $subject = $req->subject;

            match ([$req->type, $approve]) {
                ['disbursement', true] => $this->agreements->disburse($subject, $maker, $checker),
                ['expense', true] => $this->postExpense($subject, $maker, $checker),
                ['payroll_run', true] => $this->postPayroll($subject, $maker, $checker),
                ['expense', false], ['payroll_run', false] => $subject->update(['status' => 'rejected']),
                default => null,
            };
            $this->audit->log('approvals', ($approve ? 'Approved' : 'Rejected').": {$req->summary}", $req, ['note' => $note], $checker);
        });
    }

    private function postExpense(Expense $e, User $maker, User $checker): void
    {
        $entry = $this->ledger->post("Expense: {$e->description}", [[$this->expenseAccount($e->category), $e->amount, '0'], ['1010', '0', $e->amount]], $e, $maker, $checker, $e->incurred_on->toDateString());
        $e->update(['status' => 'posted', 'journal_entry_id' => $entry->id]);
    }

    private function postPayroll(PayrollRun $run, User $maker, User $checker): void
    {
        $deductions = bcsub($run->total_gross, $run->total_net, 2);
        $lines = [['5010', $run->total_gross, '0'], ['1010', '0', $run->total_net]];
        if (bccomp($deductions, '0', 2) > 0) {
            $lines[] = ['2210', '0', $deductions, 'PAYE and statutory deductions'];
        }
        $entry = $this->ledger->post('Payroll '.$run->period->format('F Y'), $lines, $run, $maker, $checker, $run->period->endOfMonth()->toDateString());
        $run->update(['status' => 'posted', 'journal_entry_id' => $entry->id]);
    }

    public function expenseAccount(string $category): string
    {
        return ['Premises' => '5020', 'Technology' => '5030', 'Marketing' => '5040'][$category] ?? '5090';
    }
}
