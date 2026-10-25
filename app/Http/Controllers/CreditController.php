<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\LoanAgreement;
use App\Services\ApplicationWorkflow;
use App\Services\CreditService;
use App\Services\RepaymentService;
use App\Services\Settings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CreditController extends Controller
{
    public function __construct(private readonly CreditService $credit) {}

    public function index(Request $request)
    {
        $statuses = ['submitted', 'screening', 'assessment', 'info_requested'];
        $q = Application::with('business')->whereIn('status', $request->status ? [$request->status] : array_merge($statuses, ['approved', 'rejected']))->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $request->user()->id))->latest('submitted_at');
        $names = \App\Models\User::whereIn('id', Application::whereNotNull('assigned_to')->pluck('assigned_to'))->pluck('name', 'id');

        return Inertia::render('credit/Index', [
            'applications' => $q->paginate(15)->withQueryString()->through(fn ($a) => ['id' => $a->id, 'business' => $a->business->display_name, 'sector' => $a->business->sector, 'status' => $a->status, 'amount' => $a->amount, 'term_months' => $a->term_months, 'submitted_at' => $a->submitted_at?->toDateString(), 'referred' => (bool) $a->referred_by, 'assigned' => $a->assigned_to ? ($names[$a->assigned_to] ?? '?') : null, 'mine' => $a->assigned_to === $request->user()->id]),
            'filters' => $request->only('status', 'mine'), 'new_count' => Application::where('status', 'submitted')->count(),
        ]);
    }

    public function show(Request $request, Application $application)
    {
        $a = $application->load('business.owner', 'documents', 'assessments.analyst', 'decisions.decider');
        $u = $request->user();
        $readOnly = $u->role->can('oversight.read_only');
        $readOnly && app(\App\Services\AuditLogger::class)->record('oversight.viewed', $application, ['page' => 'credit']);
        $suggest = in_array($a->status, ['assessment', 'screening', 'submitted']) ? $this->credit->suggest($a) : null;

        return Inertia::render('credit/Show', [
            'application' => [
                'id' => $a->id, 'status' => $a->status, 'amount' => $a->amount, 'purpose' => $a->purpose, 'term_months' => $a->term_months, 'frequency' => $a->frequency, 'interest_method' => $a->interest_method, 'rate_bp' => $a->proposed_rate_bp,
                'monthly_revenue' => $a->monthly_revenue, 'monthly_expenses' => $a->monthly_expenses, 'existing_debt' => $a->existing_debt, 'records_months' => $a->records_months, 'repayment_plan' => $a->repayment_plan, 'info_request' => $a->info_request,
                'business' => ['name' => $a->business->name, 'display_name' => $a->business->display_name, 'sector' => $a->business->sector, 'district' => $a->business->district, 'years' => $a->business->years_operating, 'description' => $a->business->description, 'registration_no' => $u->role->can('documents.view_any') ? $a->business->registration_no : null, 'assisted' => (bool) $a->business->registered_by_staff_id],
                'documents' => $a->documents->map(fn ($d) => ['id' => $d->id, 'type' => $d->type, 'name' => $d->original_name, 'size' => $d->size, 'sha256' => substr($d->sha256, 0, 12)]),
                'assessments' => $a->assessments->map(fn ($x) => ['id' => $x->id, 'analyst' => $x->analyst->name, 'score' => $x->score, 'category' => $x->risk_category, 'recommendation' => $x->recommendation, 'notes' => $x->notes, 'criteria' => $x->criteria, 'at' => $x->created_at->toDateTimeString()]),
                'decisions' => $a->decisions->map(fn ($x) => ['exception' => $x->exception_reason, 'by' => $x->decider->name, 'decision' => $x->decision, 'reason' => $x->reason, 'conditions' => $x->conditions, 'approved_amount' => $x->approved_amount, 'max_rate_bp' => $x->max_rate_bp, 'at' => $x->created_at->toDateTimeString()]),
            ],
            'suggested' => $suggest, 'eligibility' => $this->credit->eligibility($a),
            'read_only' => $readOnly, 'can_open_documents' => $u->role->can('documents.view_any'),
            'is_analyst' => $u->role->can('credit.assess'), 'is_approver' => $u->role->can('credit.approve'),
            'executive_threshold' => Settings::get('credit.executive_threshold'), 'max_rate_bp' => Settings::get('loan.max_rate_bp'),
            'transitions' => collect(['screening', 'assessment'])->filter(fn ($s) => app(ApplicationWorkflow::class)->canTransition($a, $s))->values(),
        ]);
    }

    public function advance(Request $request, Application $application)
    {
        $to = $request->validate(['to' => 'required|in:screening,assessment'])['to'];
        $to === 'screening' ? $this->credit->startScreening($application, $request->user()) : $this->credit->beginAssessment($application, $request->user());

        return back()->with('success', 'Moved to '.$to.'.');
    }

    public function requestInfo(Request $request, Application $application)
    {
        $this->credit->requestInfo($application, $request->user(), (string) $request->input('request'));

        return to_route('credit.index')->with('success', 'The applicant has been asked for more information.');
    }

    public function assess(Request $request, Application $application)
    {
        $d = $request->validate(['score' => 'required|integer|min:0|max:100', 'notes' => 'required|string|max:4000', 'recommendation' => 'required|in:approve,reject,refer']);
        $this->credit->assess($application, $request->user(), $d['score'], $d['notes'], $d['recommendation']);

        return back()->with('success', 'Assessment recorded.');
    }

    public function decide(Request $request, Application $application)
    {
        $d = $request->validate(['decision' => 'required|in:approved,rejected', 'exception_reason' => 'nullable|string|max:2000', 'reason' => 'required|string|max:4000', 'conditions' => 'nullable|string|max:2000', 'approved_amount' => 'nullable|regex:/^\d{1,12}(\.\d{1,2})?$/', 'max_rate' => 'nullable|numeric|min:0|max:100']);
        $this->credit->decide($application, $request->user(), $d['decision'], $d['reason'], $d['conditions'] ?? null, isset($d['approved_amount']) ? \App\Support\Money::toMinor($d['approved_amount']) : null, isset($d['max_rate']) ? (int) round($d['max_rate'] * 100) : null, $d['exception_reason'] ?? null);

        return to_route('credit.index')->with('success', 'Decision recorded.');
    }

    public function policy(Request $request)
    {
        return Inertia::render('credit/Policy', [
            'bands' => CreditService::bands(), 'weights' => Settings::get('credit.weights'),
            'pending' => \App\Models\ApprovalRequest::where('type', 'policy_change')->where('status', 'pending')->latest('id')->get()->map(fn ($r) => ['id' => $r->id, 'reason' => $r->reason, 'bands' => $r->payload['settings']['credit.bands'] ?? [], 'mine' => $r->requested_by === $request->user()->id]),
            'can_request' => $request->user()->role->can('credit.approve'), 'can_decide' => $request->user()->role->can('finance.approve'),
        ]);
    }

    public function requestBands(Request $request, \App\Services\ApprovalService $approvals)
    {
        $d = $request->validate(['bands' => 'required|array|min:2|max:6', 'bands.*.code' => 'required|string|max:3|distinct', 'bands.*.min' => 'required|integer|min:0|max:100', 'bands.*.name' => 'required|string|max:60', 'bands.*.definition' => 'required|string|max:300', 'reason' => 'required|string|min:10|max:500']);
        $bands = collect($d['bands'])->map(fn ($b) => ['code' => $b['code'], 'min' => (int) $b['min'], 'name' => $b['name'], 'definition' => $b['definition']])->sortByDesc('min')->values();
        if ($bands->pluck('min')->duplicates()->isNotEmpty()) {
            return back()->withErrors(['bands' => 'Every band needs a different starting score.']);
        }
        $approvals->request('policy_change', ['settings' => ['credit.bands' => $bands->all()]], $d['reason'], $request->user());

        return back()->with('success', 'Change requested. A different finance, compliance or executive user must approve it. Existing assessments keep the band they were given.');
    }

    public function markDefault(Request $request, LoanAgreement $agreement, RepaymentService $repayments)
    {
        $repayments->markDefault($agreement, $request->user(), (string) $request->input('reason'));

        return back()->with('success', 'Loan marked as defaulted.');
    }
}
