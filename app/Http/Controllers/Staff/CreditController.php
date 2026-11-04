<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\Assessment;
use App\Models\LoanApplication;
use App\Services\ApplicationWorkflow;
use App\Services\AssessmentService;
use App\Services\AuditLogger;
use App\Services\RulesService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CreditController extends Controller
{
    public function __construct(private ApplicationWorkflow $workflow, private AssessmentService $assessments, private AuditLogger $audit) {}

    public function queue(Request $request)
    {
        $status = $request->query('status', 'open');
        $apps = LoanApplication::with('business:id,legal_name,industry,region', 'product:id,name', 'assignee:id,name')
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['submitted', 'under_review', 'information_required', 'assessment_in_progress']))
            ->when($status === 'mine', fn ($q) => $q->where('assigned_to', $request->user()->id)->whereNotIn('status', ['draft', 'closed']))
            ->when(! in_array($status, ['open', 'mine', 'all'], true), fn ($q) => $q->where('status', $status))
            ->where('status', '!=', 'draft')
            ->orderByRaw("FIELD(status, 'submitted', 'under_review', 'assessment_in_progress', 'information_required') DESC")->oldest('submitted_at')
            ->paginate(15)->withQueryString();

        return Inertia::render('Staff/Credit/Queue', [
            'applications' => $apps->through(fn ($a) => [
                'id' => $a->id, 'reference' => $a->reference, 'business' => $a->business->legal_name, 'industry' => $a->business->industry,
                'product' => $a->product->name, 'amount' => $a->amount, 'term' => $a->term_months, 'status' => $a->status,
                'statusLabel' => $a->statusLabel(), 'submitted' => $a->submitted_at, 'assignee' => $a->assignee?->name,
            ]),
            'status' => $status,
            'counts' => LoanApplication::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function history(Request $request)
    {
        return Inertia::render('Staff/Credit/History', [
            'rows' => Assessment::with('application:id,reference,amount,business_profile_id', 'application.business:id,legal_name', 'assessor:id,name', 'decider:id,name')
                ->whereIn('status', ['submitted', 'approved', 'rejected'])->latest('submitted_at')->paginate(20)
                ->through(fn ($a) => [
                    'id' => $a->id, 'applicationId' => $a->loan_application_id, 'reference' => $a->application->reference, 'business' => $a->application->business->legal_name,
                    'score' => $a->total_score, 'class' => $a->final_class ?: $a->suggested_class, 'recommendation' => $a->recommendation,
                    'status' => $a->status, 'assessor' => $a->assessor->name, 'decider' => $a->decider?->name, 'submitted' => $a->submitted_at,
                ]),
        ]);
    }

    public function show(Request $request, LoanApplication $application, RulesService $rules)
    {
        $application->load([
            'business.user:id,name,email,phone,email_verified_at,phone_verified_at', 'business.user.verifications', 'product', 'documents',
            'financialRecords', 'history.user:id,name', 'informationRequests.requester:id,name', 'assignee:id,name', 'listing',
            'assessments' => fn ($q) => $q->with('scores', 'criteriaSet.criteria', 'assessor:id,name', 'decider:id,name'),
        ]);
        $assessment = $application->assessments->first();
        $r = $assessment?->ruleSet ?? $rules->active();
        $r->loadMissing('riskBands');
        $records = $application->financialRecords;
        $avgIn = $records->avg('inflow') ?: 0;
        $avgOut = $records->avg('outflow') ?: 0;
        $estInstalment = $application->amount && $application->term_months
            ? (fn ($row) => Money::add($row['principal'], $row['interest']))(Money::amortise($application->amount, $r->interest_ceiling, $application->term_months)[0]) : null;

        return Inertia::render('Staff/Credit/Application', [
            'application' => [
                ...$application->only(['id', 'reference', 'amount', 'term_months', 'purpose', 'status', 'monthly_turnover', 'employees', 'main_customers', 'risk_class', 'submitted_at']),
                'statusLabel' => $application->statusLabel(),
                'product' => $application->product->only(['name', 'min_amount', 'max_amount', 'min_months', 'max_months']),
                'assignee' => $application->assignee?->name,
                'assignedToMe' => $application->assigned_to === $request->user()->id,
                'business' => [
                    ...$application->business->only(['legal_name', 'trading_name', 'registration_number', 'business_type', 'industry', 'region', 'district', 'address', 'years_operating', 'employees', 'monthly_turnover', 'description', 'verification_status']),
                    'owner' => $application->business->user->only(['name', 'email', 'phone']),
                    'verifications' => $application->business->user->verifications->map->only(['kind', 'status']),
                ],
                'documents' => $application->documents->map->only(['id', 'type', 'original_name', 'mime', 'size', 'status', 'created_at']),
                'records' => $records->map(fn ($f) => ['period' => $f->period->format('M Y'), 'inflow' => $f->inflow, 'outflow' => $f->outflow]),
                'cash' => ['avgIn' => Money::of((string) $avgIn), 'avgOut' => Money::of((string) $avgOut), 'instalment' => $estInstalment,
                    'cover' => $estInstalment && (float) $estInstalment > 0 ? round(($avgIn - $avgOut) / (float) $estInstalment, 2) : null],
                'history' => $application->history->map(fn ($h) => ['to' => $h->to_status, 'note' => $h->note, 'by' => $h->user?->name, 'at' => $h->created_at]),
                'requests' => $application->informationRequests->map(fn ($q) => [...$q->only(['id', 'message', 'response', 'status', 'created_at', 'responded_at']), 'by' => $q->requester->name]),
                'listing' => $application->listing?->only(['reference', 'status']),
            ],
            'assessment' => $assessment ? [
                ...$assessment->only(['id', 'status', 'total_score', 'suggested_class', 'final_class', 'override_reason', 'recommendation', 'comments', 'checks', 'risks', 'submitted_at', 'decided_at', 'decision_note']),
                'mine' => $assessment->assessor_id === $request->user()->id,
                'assessor' => $assessment->assessor->name, 'decider' => $assessment->decider?->name,
                'criteriaVersion' => $assessment->criteriaSet->version,
                'criteria' => $assessment->criteriaSet->criteria->map->only(['id', 'label', 'weight', 'guidance']),
                'scores' => $assessment->scores->mapWithKeys(fn ($s) => [$s->criterion_id => $s->score]),
                'notes' => $assessment->scores->mapWithKeys(fn ($s) => [$s->criterion_id => $s->note]),
            ] : null,
            'rules' => ['version' => $r->version, 'bands' => $r->riskBands->map->only(['risk_class', 'label', 'min_score', 'max_amount', 'max_months'])],
            'can' => ['approve' => $request->user()->hasPermission('applications.approve')],
        ]);
    }

    public function review(Request $request, LoanApplication $application)
    {
        $this->workflow->startReview($application, $request->user());

        return back()->with('success', 'Application assigned to you and moved to review.');
    }

    public function requestInformation(Request $request, LoanApplication $application)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:1000']]);
        $this->workflow->requestInformation($application, $request->user(), $data['message']);

        return back()->with('success', 'Request sent to the business by SMS and email.');
    }

    public function startAssessment(Request $request, LoanApplication $application)
    {
        abort_unless($application->assigned_to === $request->user()->id, 403, 'Only the assigned officer can start the assessment.');
        $this->assessments->start($application, $request->user());

        return back()->with('success', 'Assessment started.');
    }

    public function saveAssessment(Request $request, Assessment $assessment)
    {
        $data = $request->validate([
            'scores' => ['array'], 'scores.*' => ['integer', 'between:1,5'],
            'notes' => ['array'], 'notes.*' => ['nullable', 'string', 'max:300'],
            'checks' => ['array'], 'checks.*' => ['boolean'],
            'risks' => ['array', 'max:10'], 'risks.*' => ['nullable', 'string', 'max:300'],
            'final_class' => ['nullable', 'in:A+,A,B,C'],
            'override_reason' => ['nullable', 'string', 'max:500'],
            'recommendation' => ['nullable', 'in:approve,reject'],
            'comments' => ['nullable', 'string', 'max:3000'],
        ]);
        $a = $this->assessments->save($assessment, $data, $request->user());
        if ($request->boolean('submit_now')) {
            $this->assessments->submit($a->refresh(), $request->user());

            return back()->with('success', 'Recommendation submitted for approval.');
        }

        return back()->with('success', 'Assessment saved.');
    }

    public function document(Request $request, ApplicationDocument $document)
    {
        $this->audit->log('documents', "Viewed document {$document->original_name}", $document->application, [], $request->user());

        return Storage::disk(config('banyira.documents_disk'))->download($document->path, $document->original_name);
    }

    public function reviewDocument(Request $request, ApplicationDocument $document)
    {
        $data = $request->validate(['status' => ['required', 'in:accepted,rejected']]);
        $document->update(['status' => $data['status'], 'reviewed_by' => $request->user()->id]);
        $this->audit->log('documents', ucfirst($data['status'])." document {$document->type}", $document->application, [], $request->user());

        return back()->with('success', 'Document marked as '.$data['status'].'.');
    }
}
