<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\AgreementParty;
use App\Models\ApprovalRequest;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\AgreementService;
use App\Services\ApprovalService;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Finance workspace. Banyira's own money (revenue, expenses, operating cash) is
 * always reported apart from client money (investor capital, borrower
 * obligations, principal and interest held for investors).
 */
class FinanceController extends Controller
{
    public function __construct(private LedgerService $ledger, private ApprovalService $approvals) {}

    public function overview()
    {
        $c = fn ($cat, $from = null) => $this->ledger->categoryTotal($cat, $from);
        $monthStart = now()->startOfMonth()->toDateString();

        $monthly = DB::table('journal_lines')->join('ledger_accounts as a', 'a.id', '=', 'journal_lines.ledger_account_id')
            ->join('journal_entries as e', 'e.id', '=', 'journal_lines.journal_entry_id')
            ->whereIn('a.category', ['service_charges', 'banyira_revenue', 'operating_expenses'])->where('a.type', '!=', 'liability')
            ->where('e.entry_date', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(e.entry_date, '%Y-%m') m, SUM(CASE WHEN a.type='revenue' THEN credit-debit ELSE 0 END) rev, SUM(CASE WHEN a.type='expense' THEN debit-credit ELSE 0 END) exp")
            ->groupBy('m')->orderBy('m')->get();

        return Inertia::render('Staff/Finance/Overview', [
            'banyira' => [
                'revenueMonth' => Money::add($c('service_charges', $monthStart), $c('banyira_revenue', $monthStart)),
                'expensesMonth' => $c('operating_expenses', $monthStart),
                'revenueTotal' => Money::add($c('service_charges'), $c('banyira_revenue')),
                'expensesTotal' => $c('operating_expenses'),
                'cash' => $c('banyira_cash'),
            ],
            'client' => [
                'cash' => $c('client_cash'),
                'awaitingDisbursement' => collect($this->ledger->balances('investor_capital'))->firstWhere('code', '2010')['balance'] ?? '0.00',
                'borrowerOutstanding' => $c('borrower_obligations'),
                'principalDue' => $c('principal_repayments'),
                'interestDue' => $c('interest_repayments'),
            ],
            'monthly' => $monthly->map(fn ($r) => ['l' => date('M', strtotime($r->m.'-01')), 'v' => (float) $r->rev, 'v2' => (float) $r->exp]),
            'pending' => [
                'checker' => ApprovalRequest::where('status', 'pending')->count(),
                'reconcile' => Payment::where('status', 'pending')->count(),
                'funding' => AgreementParty::whereNull('funds_confirmed_at')->where('role', 'investor')->whereHas('agreement', fn ($q) => $q->where('status', 'awaiting_funding'))->count(),
            ],
        ]);
    }

    public function funding()
    {
        $agreements = Agreement::whereIn('status', ['awaiting_signatures', 'awaiting_funding', 'awaiting_disbursement'])
            ->with('parties.user:id,name', 'listing:id,public_name', 'borrower:id,name')->oldest('id')->get();
        $pendingRequests = ApprovalRequest::where('type', 'disbursement')->where('status', 'pending')->pluck('subject_id')->all();

        return Inertia::render('Staff/Finance/Funding', [
            'agreements' => $agreements->map(fn ($a) => [
                'id' => $a->id, 'reference' => $a->reference, 'business' => $a->listing->public_name, 'status' => $a->status,
                'principal' => $a->principal_total, 'fee' => $a->service_fee, 'feeRate' => $a->fee_rate, 'net' => Money::sub($a->principal_total, $a->service_fee),
                'requested' => in_array($a->id, $pendingRequests, true),
                'parties' => $a->parties->map(fn ($p) => ['id' => $p->id, 'name' => $p->user->name, 'role' => $p->role, 'principal' => $p->principal, 'signed' => $p->signed_at, 'funded' => $p->funds_confirmed_at]),
            ]),
        ]);
    }

    public function confirmFunds(Request $request, AgreementParty $party, AgreementService $service)
    {
        $data = $request->validate(['provider_reference' => ['required', 'string', 'max:60', 'unique:payments,provider_reference']]);
        $service->confirmInvestorFunds($party, $request->user(), $data['provider_reference']);

        return back()->with('success', 'Investor funds confirmed and recorded in the client account.');
    }

    public function requestDisbursement(Request $request, Agreement $agreement)
    {
        abort_unless($agreement->status === 'awaiting_disbursement', 422, 'This agreement is not ready for disbursement.');
        $agreement->load('listing:id,public_name');
        $this->approvals->request('disbursement', $agreement, "Disburse {$agreement->reference} to {$agreement->listing->public_name}", $agreement->principal_total, $request->user());

        return back()->with('success', 'Disbursement sent for approval by a second finance officer.');
    }

    public function reconciliation()
    {
        return Inertia::render('Staff/Finance/Reconciliation', [
            'pending' => Payment::where('status', 'pending')->with('agreement:id,reference', 'payer:id,name')->oldest('id')->get()
                ->map(fn ($p) => [...$p->only(['id', 'reference', 'amount', 'provider', 'payer_msisdn', 'is_demo', 'created_at']), 'agreement' => $p->agreement?->reference, 'payer' => $p->payer?->name]),
            'recent' => Payment::where('status', '!=', 'pending')->with('agreement:id,reference', 'payer:id,name')->latest('id')->limit(25)->get()
                ->map(fn ($p) => [...$p->only(['id', 'reference', 'amount', 'purpose', 'direction', 'status', 'provider_reference', 'confirmed_at', 'is_demo']), 'agreement' => $p->agreement?->reference, 'payer' => $p->payer?->name]),
        ]);
    }

    public function confirmPayment(Request $request, Payment $payment, PaymentService $payments)
    {
        $data = $request->validate(['provider_reference' => ['required', 'string', 'max:60', 'unique:payments,provider_reference,'.$payment->id]]);
        $payments->confirm($payment, $request->user(), $data['provider_reference']);

        return back()->with('success', "Payment {$payment->reference} confirmed and allocated to the repayment schedule.");
    }

    public function failPayment(Request $request, Payment $payment, PaymentService $payments)
    {
        $payments->fail($payment, $request->user());

        return back()->with('success', 'Payment marked as not received.');
    }

    public function checker(Request $request)
    {
        return Inertia::render('Staff/Finance/Approvals', [
            'requests' => ApprovalRequest::with('requester:id,name', 'decider:id,name')->latest('id')->limit(50)->get()
                ->map(fn ($r) => [...$r->only(['id', 'type', 'summary', 'amount', 'status', 'created_at', 'decided_at', 'decision_note']), 'by' => $r->requester->name, 'decidedBy' => $r->decider?->name, 'mine' => $r->requested_by === $request->user()->id]),
        ]);
    }

    public function decide(Request $request, ApprovalRequest $approval)
    {
        $data = $request->validate(['approve' => ['required', 'boolean'], 'note' => ['nullable', 'string', 'max:300']]);
        abort_if($approval->type === 'payroll_run' && ! $request->user()->hasPermission('payroll.view'), 403);
        $this->approvals->decide($approval, $data['approve'], $request->user(), $data['note'] ?? null);

        return back()->with('success', $data['approve'] ? 'Approved and posted to the ledger.' : 'Request rejected.');
    }

    public function ledger(Request $request)
    {
        $cat = $request->query('category');
        $entries = JournalEntry::with('lines.account:id,code,name,category', 'creator:id,name', 'approver:id,name')
            ->when($cat, fn ($q) => $q->whereHas('lines.account', fn ($a) => $a->where('category', $cat)))
            ->latest('id')->paginate(20)->withQueryString();

        return Inertia::render('Staff/Finance/Ledger', [
            'entries' => $entries->through(fn ($e) => [
                ...$e->only(['id', 'reference', 'entry_date', 'description', 'is_demo', 'reverses_id']),
                'by' => $e->creator?->name, 'approvedBy' => $e->approver?->name, 'reversible' => in_array($e->source_type, ['expense'], true) && ! $e->reverses_id,
                'lines' => $e->lines->map(fn ($l) => ['code' => $l->account->code, 'account' => $l->account->name, 'category' => $l->account->category, 'debit' => $l->debit, 'credit' => $l->credit]),
            ]),
            'balances' => $this->ledger->balances(),
            'category' => $cat,
        ]);
    }

    public function reverse(Request $request, JournalEntry $entry)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);
        abort_unless($entry->source_type === 'expense', 422, 'Only expense entries can be reversed here. Payments are corrected through reconciliation.');
        $this->ledger->reverse($entry, $request->user(), $data['reason']);

        return back()->with('success', 'Reversal posted.');
    }

    public function expenses()
    {
        return Inertia::render('Staff/Finance/Expenses', [
            'expenses' => Expense::with('recorder:id,name')->latest('incurred_on')->paginate(20)->through(fn ($e) => [...$e->only(['id', 'category', 'description', 'amount', 'incurred_on', 'status']), 'by' => $e->recorder->name]),
            'categories' => ['Premises', 'Technology', 'Marketing', 'Travel', 'Professional fees', 'Other'],
        ]);
    }

    public function storeExpense(Request $request)
    {
        $data = $request->validate(['category' => ['required', 'string', 'max:40'], 'description' => ['required', 'string', 'max:200'], 'amount' => ['required', 'numeric', 'min:1'], 'incurred_on' => ['required', 'date', 'before_or_equal:today']]);
        $e = Expense::create([...$data, 'recorded_by' => $request->user()->id, 'status' => 'pending_approval']);
        $this->approvals->request('expense', $e, "Expense: {$e->description}", $e->amount, $request->user());

        return back()->with('success', 'Expense recorded and sent for approval.');
    }

    public function payroll(Request $request)
    {
        abort_unless($request->user()->hasPermission('payroll.view'), 403);
        app(\App\Services\AuditLogger::class)->log('payroll', 'Viewed payroll', null, [], $request->user());

        return Inertia::render('Staff/Finance/Payroll', [
            'runs' => PayrollRun::with('items.user:id,name', 'items.user.employeeProfile:id,user_id,job_title', 'preparer:id,name')->latest('period')->get()
                ->map(fn ($r) => [...$r->only(['id', 'period', 'total_gross', 'total_net', 'status']), 'by' => $r->preparer->name,
                    'items' => $r->items->map(fn ($i) => ['name' => $i->user->name, 'title' => $i->user->employeeProfile?->job_title, 'gross' => $i->gross, 'deductions' => $i->deductions, 'net' => $i->net])]),
            'canPrepare' => $request->user()->hasPermission('payroll.manage'),
            'nextPeriod' => now()->startOfMonth()->toDateString(),
            'employees' => User::where('type', 'employee')->where('status', 'active')->with('employeeProfile:id,user_id,job_title')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'title' => $u->employeeProfile?->job_title]),
        ]);
    }

    public function storePayroll(Request $request)
    {
        abort_unless($request->user()->hasPermission('payroll.manage'), 403);
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m', 'unique:payroll_runs,period'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.user_id' => ['required', 'exists:users,id'],
            'items.*.gross' => ['required', 'numeric', 'min:0'],
            'items.*.deductions' => ['required', 'numeric', 'min:0', 'lte:items.*.gross'],
        ]);
        $run = DB::transaction(function () use ($data, $request) {
            $run = PayrollRun::create(['period' => $data['period'].'-01', 'total_gross' => '0', 'total_net' => '0', 'prepared_by' => $request->user()->id, 'status' => 'pending_approval']);
            $g = '0';
            $n = '0';
            foreach ($data['items'] as $i) {
                $net = Money::sub((string) $i['gross'], (string) $i['deductions']);
                $run->items()->create(['user_id' => $i['user_id'], 'gross' => $i['gross'], 'deductions' => $i['deductions'], 'net' => $net]);
                $g = Money::add($g, (string) $i['gross']);
                $n = Money::add($n, $net);
            }
            $run->update(['total_gross' => $g, 'total_net' => $n]);

            return $run;
        });
        $this->approvals->request('payroll_run', $run, 'Payroll '.$run->period->format('F Y'), $run->total_gross, $request->user());

        return back()->with('success', 'Payroll prepared and sent for approval.');
    }

    public function reports()
    {
        return Inertia::render('Staff/Finance/Reports', [
            'balances' => $this->ledger->balances(),
            'periods' => collect(range(0, 5))->map(fn ($i) => now()->subMonths($i)->format('Y-m')),
        ]);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        $from = $month.'-01';
        $to = date('Y-m-t', strtotime($from));

        return response()->streamDownload(function () use ($type, $from, $to) {
            $out = fopen('php://output', 'w');
            if ($type === 'transactions') {
                fputcsv($out, ['Date', 'Entry', 'Description', 'Account', 'Category', 'Client money', 'Debit', 'Credit', 'Demo']);
                JournalEntry::with('lines.account')->whereBetween('entry_date', [$from, $to])->orderBy('id')->each(function ($e) use ($out) {
                    foreach ($e->lines as $l) {
                        fputcsv($out, [$e->entry_date->toDateString(), $e->reference, $e->description, $l->account->code.' '.$l->account->name, $l->account->category, $l->account->is_client_money ? 'yes' : 'no', $l->debit, $l->credit, $e->is_demo ? 'yes' : 'no']);
                    }
                });
            } else {
                $client = $type === 'client-funds';
                fputcsv($out, [$client ? 'Client funds statement' : 'Banyira income and expenditure', "{$from} to {$to}"]);
                fputcsv($out, ['Account', 'Category', 'Balance']);
                foreach ($this->ledger->balances() as $b) {
                    if ($b['client'] === $client && ($client || in_array($b['type'], ['revenue', 'expense'], true))) {
                        fputcsv($out, [$b['code'].' '.$b['name'], $b['category'], $b['balance']]);
                    }
                }
            }
            fclose($out);
        }, "banyira-{$type}-{$from}.csv", ['Content-Type' => 'text/csv']);
    }
}
