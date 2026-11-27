<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Assessment;
use App\Models\Investment;
use App\Models\Listing;
use App\Models\LoanApplication;
use App\Models\Payment;
use App\Models\RepaymentSchedule;
use App\Models\User;
use App\Services\LedgerService;
use App\Support\Money;
use App\Support\Sql;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExecutiveController extends Controller
{
    public function __construct(private LedgerService $ledger) {}

    public function overview()
    {
        $decided = LoanApplication::whereNotNull('decided_at')->count();
        $approved = Assessment::where('status', 'approved')->count();
        $revenue = Money::add($this->ledger->categoryTotal('service_charges'), $this->ledger->categoryTotal('banyira_revenue'));
        $expenses = $this->ledger->categoryTotal('operating_expenses');
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));
        $apps = LoanApplication::whereNotNull('submitted_at')->where('submitted_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw(Sql::month('submitted_at').' m, COUNT(*) n')->groupBy('m')->pluck('n', 'm');

        return Inertia::render('Staff/Executive/Overview', [
            'stats' => [
                'businesses' => User::where('type', 'business')->count(),
                'investors' => User::where('type', 'investor')->count(),
                'applications' => LoanApplication::whereNotNull('submitted_at')->count(),
                'approvalRate' => $decided ? round($approved / $decided * 100) : null,
                'requested' => Money::of((string) LoanApplication::whereNotNull('submitted_at')->sum('amount')),
                'funded' => Money::of((string) Agreement::whereIn('status', ['active', 'completed'])->sum('principal_total')),
                'listings' => Listing::where('status', 'open')->count(),
                'fundedSmes' => Agreement::whereIn('status', ['active', 'completed'])->distinct('borrower_id')->count('borrower_id'),
                'participating' => Investment::distinct('investor_id')->count('investor_id'),
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => Money::sub($revenue, $expenses),
            ],
            'applicationsByMonth' => $months->map(fn ($m) => ['l' => date('M', strtotime($m.'-01')), 'v' => (int) ($apps[$m] ?? 0)]),
            'byClass' => Agreement::whereIn('agreements.status', ['active', 'completed'])->join('listings', 'listings.id', '=', 'agreements.listing_id')
                ->selectRaw('listings.risk_class l, SUM(principal_total) v')->groupBy('listings.risk_class')->get()->map(fn ($r) => ['l' => 'Class '.$r->l, 'v' => (float) $r->v]),
            'pipeline' => LoanApplication::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function risk()
    {
        $due = RepaymentSchedule::whereDate('due_date', '<=', now());
        $dueCount = (clone $due)->count();
        $paidOnTime = (clone $due)->where('status', 'paid')->count();
        $overdue = RepaymentSchedule::where('status', 'overdue')->selectRaw('COUNT(*) n, COALESCE(SUM(principal_due + interest_due - principal_paid - interest_paid),0) amt')->first();

        return Inertia::render('Staff/Executive/Risk', [
            'stats' => [
                'outstanding' => $this->ledger->categoryTotal('borrower_obligations'),
                'dueInstalments' => $dueCount,
                'onTimeRate' => $dueCount ? round($paidOnTime / $dueCount * 100, 1) : null,
                'overdueCount' => $overdue->n,
                'overdueAmount' => Money::of($overdue->amt),
                'inArrears' => Investment::where('status', 'in_arrears')->count(),
                'confirmedRepayments' => Money::of((string) Payment::where('purpose', 'repayment')->where('status', 'confirmed')->sum('amount')),
            ],
            'byClass' => Investment::join('agreements as a', 'a.id', '=', 'investments.agreement_id')->join('listings as l', 'l.id', '=', 'a.listing_id')
                ->selectRaw("l.risk_class c, COUNT(*) n, SUM(investments.principal - investments.principal_repaid) outstanding, SUM(CASE WHEN investments.status = 'in_arrears' THEN 1 ELSE 0 END) arrears")
                ->groupBy('l.risk_class')->orderBy('l.risk_class')->get()->map(fn ($r) => ['class' => $r->c, 'count' => $r->n, 'outstanding' => Money::of($r->outstanding), 'arrears' => (int) $r->arrears]),
            'upcoming' => RepaymentSchedule::where('status', '!=', 'paid')->whereBetween('due_date', [now(), now()->addDays(30)])->count(),
        ]);
    }

    public function people()
    {
        $credit = DB::table('assessments')->join('users', 'users.id', '=', 'assessments.assessor_id')->whereNotNull('submitted_at')
            ->selectRaw('users.name, COUNT(*) n, AVG('.Sql::hoursBetween('assessments.created_at', 'assessments.submitted_at').') hours')->groupBy('users.name')->get();
        $finance = DB::table('payments')->join('users', 'users.id', '=', 'payments.confirmed_by')->where('payments.status', 'confirmed')
            ->selectRaw('users.name, COUNT(*) n')->groupBy('users.name')->get();
        $sales = DB::table('lead_activities')->join('users', 'users.id', '=', 'lead_activities.user_id')
            ->selectRaw('users.name, COUNT(*) n')->groupBy('users.name')->get();

        return Inertia::render('Staff/Executive/People', [
            'rows' => collect()
                ->concat($credit->map(fn ($r) => ['name' => $r->name, 'area' => 'Credit', 'measure' => 'Assessments submitted', 'value' => $r->n, 'extra' => round((float) $r->hours, 1).' h average']))
                ->concat($finance->map(fn ($r) => ['name' => $r->name, 'area' => 'Finance', 'measure' => 'Payments confirmed', 'value' => $r->n, 'extra' => null]))
                ->concat($sales->map(fn ($r) => ['name' => $r->name, 'area' => 'Sales', 'measure' => 'Lead activities logged', 'value' => $r->n, 'extra' => null]))
                ->values(),
        ]);
    }
}
