<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\LoanApplication;
use App\Models\RuleSet;
use App\Services\AssessmentService;
use App\Services\MarketplaceService;
use App\Services\RulesService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Management approvals: credit recommendations, marketplace publication, rule changes. */
class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();

        return Inertia::render('Staff/Approvals', [
            'assessments' => $u->hasPermission('applications.approve') ? Assessment::where('status', 'submitted')
                ->with('application:id,reference,amount,term_months,purpose,business_profile_id', 'application.business:id,legal_name,industry', 'assessor:id,name')->oldest('submitted_at')->get()
                ->map(fn ($a) => [
                    'id' => $a->id, 'applicationId' => $a->loan_application_id, 'reference' => $a->application->reference, 'business' => $a->application->business->legal_name,
                    'industry' => $a->application->business->industry, 'amount' => $a->application->amount, 'term' => $a->application->term_months, 'purpose' => $a->application->purpose,
                    'score' => $a->total_score, 'suggested' => $a->suggested_class, 'final' => $a->final_class, 'override' => $a->override_reason,
                    'recommendation' => $a->recommendation, 'comments' => $a->comments, 'assessor' => $a->assessor->name, 'mine' => $a->assessor_id === $u->id, 'submitted' => $a->submitted_at,
                ]) : [],
            'toPublish' => $u->hasPermission('listings.publish') ? LoanApplication::where('status', 'approved')->with('business:id,legal_name,description,industry,region')->get()
                ->map(fn ($a) => ['id' => $a->id, 'reference' => $a->reference, 'business' => $a->business->legal_name, 'summary' => $a->business->description, 'purpose' => $a->purpose,
                    'amount' => $a->amount, 'term' => $a->term_months, 'risk' => $a->risk_class]) : [],
            'rules' => $u->hasPermission('rules.approve') ? RuleSet::where('status', 'pending_approval')->with('feeTiers', 'riskBands', 'proposer:id,name')->get()
                ->map(fn ($r) => [...$r->only(['id', 'version', 'effective_from', 'interest_ceiling', 'notes']), 'by' => $r->proposer?->name, 'mine' => $r->proposed_by === $u->id,
                    'fees' => $r->feeTiers->map->only(['upper_bound', 'rate']), 'bands' => $r->riskBands->map->only(['risk_class', 'min_score', 'max_amount', 'max_months'])]) : [],
            'ceiling' => app(RulesService::class)->active()->interest_ceiling,
        ]);
    }

    public function decideAssessment(Request $request, Assessment $assessment, AssessmentService $service)
    {
        $data = $request->validate(['approve' => ['required', 'boolean'], 'note' => ['nullable', 'string', 'max:500', 'required_if:approve,false']]);
        $service->decide($assessment, $data['approve'], $request->user(), $data['note'] ?? null);

        return back()->with('success', $data['approve'] ? 'Approved. You can now publish it to the marketplace.' : 'Application rejected. The business has been told.');
    }

    public function publish(Request $request, LoanApplication $application, MarketplaceService $market)
    {
        $data = $request->validate([
            'public_name' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:600'],
            'purpose' => ['required', 'string', 'max:200'],
            'indicative_rate' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);
        $listing = $market->publish($application, $request->user(), $data);

        return back()->with('success', "Published as {$listing->reference}. Investors can now make offers.");
    }

    public function decideRules(Request $request, RuleSet $ruleSet, RulesService $rules)
    {
        $data = $request->validate(['approve' => ['required', 'boolean'], 'note' => ['nullable', 'string', 'max:300']]);
        $rules->decide($ruleSet, $data['approve'], $request->user(), $data['note'] ?? null);

        return back()->with('success', $data['approve'] ? 'Rules approved. They apply from their effective date.' : 'Rule proposal rejected.');
    }
}
