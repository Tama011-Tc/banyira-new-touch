<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Business;
use App\Services\ApplicationWorkflow;
use App\Services\AuditLogger;
use App\Services\CreditService;
use App\Services\Settings;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** The applicant side: a business owner, or the sales rep who registered the business with the owner's consent. */
class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationWorkflow $workflow, private readonly CreditService $credit, private readonly AuditLogger $audit) {}

    private function ownedBusinessIds(Request $r): array
    {
        $u = $r->user();

        return $u->role->value === 'sales' ? Business::where('registered_by_staff_id', $u->id)->pluck('id')->all() : Business::where('owner_id', $u->id)->pluck('id')->all();
    }

    /** Applicants can reach only their own applications; anyone else gets a 404, as if it did not exist. */
    public function find(Request $r, int $id): Application
    {
        return Application::whereIn('business_id', $this->ownedBusinessIds($r))->with('business')->findOrFail($id);
    }

    public function index(Request $request)
    {
        $apps = Application::whereIn('business_id', $this->ownedBusinessIds($request))->latest('id')->get();

        return Inertia::render('applications/Index', [
            'has_business' => $request->user()->business()->exists() || $request->user()->role->value === 'sales',
            'applications' => $apps->map(fn ($a) => ['id' => $a->id, 'status' => $a->status, 'amount' => $a->amount, 'purpose' => $a->purpose, 'term_months' => $a->term_months, 'info_request' => $a->info_request, 'submitted_at' => $a->submitted_at?->toDateString()]),
        ]);
    }

    private function rules(): array
    {
        return [
            'amount' => ['required', Money::DECIMAL_RULE], 'purpose' => 'required|string|min:20|max:2000',
            'term_months' => ['required', 'integer', 'min:1', 'max:'.Settings::get('loan.max_term_months')], 'frequency' => ['required', Rule::in(['monthly', 'weekly'])],
            'interest_method' => ['required', Rule::in(['flat', 'reducing'])], 'proposed_rate' => ['required', 'numeric', 'min:0', 'max:'.(Settings::get('loan.max_rate_bp') / 100)],
            'monthly_revenue' => ['required', Money::DECIMAL_RULE], 'monthly_expenses' => ['required', Money::DECIMAL_RULE], 'existing_debt' => ['required', Money::DECIMAL_RULE],
            'repayment_plan' => 'nullable|string|max:2000',
        ];
    }

    private function fields(array $d): array
    {
        return [
            'amount' => Money::toMinor($d['amount']), 'purpose' => $d['purpose'], 'term_months' => (int) $d['term_months'], 'frequency' => $d['frequency'], 'interest_method' => $d['interest_method'],
            'proposed_rate_bp' => (int) round($d['proposed_rate'] * 100), 'monthly_revenue' => Money::toMinor($d['monthly_revenue']), 'monthly_expenses' => Money::toMinor($d['monthly_expenses']),
            'existing_debt' => Money::toMinor($d['existing_debt']), 'repayment_plan' => $d['repayment_plan'] ?? null,
        ];
    }

    public function create(Request $request)
    {
        $b = $request->integer('business') ?: $request->user()->business?->id;
        abort_unless($b && in_array($b, $this->ownedBusinessIds($request)), 404);

        return Inertia::render('applications/Form', ['application' => null, 'business_id' => $b, 'limits' => ['max_term' => Settings::get('loan.max_term_months'), 'max_rate' => Settings::get('loan.max_rate_bp') / 100]]);
    }

    public function store(Request $request)
    {
        $b = $request->integer('business_id');
        abort_unless(in_array($b, $this->ownedBusinessIds($request)), 404);
        $a = Application::create($this->fields($request->validate($this->rules())) + ['business_id' => $b, 'status' => 'draft', 'assisted_by' => $request->user()->role->value === 'sales' ? $request->user()->id : null]);
        $this->audit->record('application.created', $a);

        return to_route('applications.show', $a)->with('success', 'Draft saved. Add the required documents, then submit.');
    }

    public function show(Request $request, int $application)
    {
        $a = $this->find($request, $application);
        $a->load('documents', 'decisions');

        return Inertia::render('applications/Show', [
            'application' => [
                'id' => $a->id, 'status' => $a->status, 'amount' => $a->amount, 'purpose' => $a->purpose, 'term_months' => $a->term_months, 'frequency' => $a->frequency, 'interest_method' => $a->interest_method,
                'rate_bp' => $a->proposed_rate_bp, 'monthly_revenue' => $a->monthly_revenue, 'monthly_expenses' => $a->monthly_expenses, 'existing_debt' => $a->existing_debt, 'repayment_plan' => $a->repayment_plan,
                'info_request' => $a->info_request, 'assisted_by_name' => $a->assisted_by ? \App\Models\User::whereKey($a->assisted_by)->value('name') : null, 'business' => $a->business->display_name, 'editable' => in_array($a->status, ['draft', 'info_requested']),
                'documents' => $a->documents->map(fn ($d) => ['id' => $d->id, 'type' => $d->type, 'name' => $d->original_name, 'size' => $d->size]),
                'decision' => $a->decisions->last() ? $a->decisions->last()->only('decision', 'reason', 'conditions') : null,
                'history' => \App\Models\AuditLog::where('action', 'application.status')->where('subject_type', 'Application')->where('subject_id', $a->id)->orderBy('id')->get()->map(fn ($l) => ['at' => $l->created_at->toDateTimeString(), 'to' => $l->meta['to'] ?? null, 'reason' => $l->meta['reason'] ?? null]),
            ],
            'problems' => $a->status === 'draft' || $a->status === 'info_requested' ? $this->workflow->submissionProblems($a) : [],
            'doc_types' => ['required' => config('banyira.documents.required'), 'optional' => config('banyira.documents.optional')],
            'limits' => ['max_term' => Settings::get('loan.max_term_months'), 'max_rate' => Settings::get('loan.max_rate_bp') / 100],
        ]);
    }

    public function update(Request $request, int $application)
    {
        $a = $this->find($request, $application);
        abort_unless(in_array($a->status, ['draft', 'info_requested']), 403);
        $f = $this->fields($request->validate($this->rules()));
        $this->credit->amendTerms($a, $request->user(), $f);
        $a->update($f);

        return back()->with('success', 'Application saved.');
    }

    public function submit(Request $request, int $application)
    {
        $a = $this->find($request, $application);
        $this->workflow->submit($a, $request->user());

        return to_route('applications.show', $a)->with('success', 'Submitted for credit review.');
    }

    public function withdraw(Request $request, int $application)
    {
        $a = $this->find($request, $application);
        $this->workflow->transition($a, 'withdrawn', $request->user(), 'Withdrawn by applicant');

        return to_route('applications.index')->with('success', 'Application withdrawn.');
    }

    public function upload(Request $request, int $application)
    {
        $a = $this->find($request, $application);
        abort_unless(in_array($a->status, ['draft', 'info_requested']), 403);
        $d = $request->validate([
            'type' => ['required', Rule::in(array_merge(config('banyira.documents.required'), config('banyira.documents.optional')))],
            'file' => ['required', 'file', 'max:'.config('banyira.documents.max_kilobytes'), 'mimes:'.implode(',', config('banyira.documents.mimes'))],
        ]);
        $f = $request->file('file');
        // Private disk, random name: there is never a public URL for a confidential document.
        $path = $f->storeAs("documents/{$a->id}", bin2hex(random_bytes(16)).'.'.$f->getClientOriginalExtension(), 'local');
        $doc = ApplicationDocument::create(['application_id' => $a->id, 'uploaded_by' => $request->user()->id, 'type' => $d['type'], 'original_name' => mb_substr(basename($f->getClientOriginalName()), 0, 120), 'path' => $path, 'mime' => $f->getMimeType() ?: 'application/octet-stream', 'size' => $f->getSize(), 'sha256' => hash_file('sha256', $f->getRealPath())]);
        $this->audit->record('document.uploaded', $doc, ['type' => $d['type']]);

        return back()->with('success', 'Document uploaded.');
    }

    /** Applicant, assisting sales rep, or credit/compliance staff with the document permission. Never investors. */
    public function download(Request $request, ApplicationDocument $document)
    {
        $u = $request->user();
        $a = $document->application()->with('business')->first();
        $allowed = $u->role->can('documents.view_any') || ($a->business->owner_id === $u->id) || ($u->role->value === 'sales' && $a->assisted_by === $u->id);
        abort_unless($allowed, 404);
        $this->audit->record('document.viewed', $document, ['by_role' => $u->role->value]);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function destroyDocument(Request $request, ApplicationDocument $document)
    {
        $a = $this->find($request, $document->application_id);
        abort_unless(in_array($a->status, ['draft', 'info_requested']), 403);
        Storage::disk('local')->delete($document->path);
        $this->audit->record('document.removed', $document, ['type' => $document->type]);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }
}
