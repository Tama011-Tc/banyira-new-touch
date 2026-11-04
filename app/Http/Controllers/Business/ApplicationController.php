<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationRequest;
use App\Models\ApplicationDocument;
use App\Models\InformationRequest;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Services\ApplicationWorkflow;
use App\Services\RulesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public const DOCUMENT_TYPES = ['Business registration certificate', 'Owner national ID', 'Bank or mobile money statements', 'Proof of business address', 'Supplier quotation or invoice', 'Financial statements', 'Business plan or budget', 'Other'];

    public function __construct(private ApplicationWorkflow $workflow, private RulesService $rules) {}

    public function index(Request $request)
    {
        $apps = $request->user()->businessProfile->applications()->with('product:id,name')->latest('id')->get();

        return Inertia::render('Business/Applications/Index', [
            'applications' => $apps->map(fn ($a) => [
                'id' => $a->id, 'reference' => $a->reference, 'product' => $a->product->name, 'amount' => $a->amount, 'term' => $a->term_months,
                'status' => $a->status, 'statusLabel' => $a->statusLabel(), 'submitted' => $a->submitted_at, 'updated' => $a->updated_at, 'risk' => $a->risk_class,
            ]),
            'products' => LoanProduct::active()->get(['id', 'name', 'summary']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['loan_product_id' => ['required', 'exists:loan_products,id']]);
        $app = $this->workflow->createDraft($request->user()->load('businessProfile'), $data + [
            'monthly_turnover' => $request->user()->businessProfile->monthly_turnover,
            'employees' => $request->user()->businessProfile->employees,
        ]);

        return redirect("/business/applications/{$app->id}/edit");
    }

    public function edit(Request $request, LoanApplication $application)
    {
        $this->authorize('update', $application);
        $application->load('documents', 'financialRecords', 'product', 'informationRequests');

        return Inertia::render('Business/Applications/Edit', [
            'application' => [
                ...$application->only(['id', 'reference', 'loan_product_id', 'amount', 'term_months', 'purpose', 'monthly_turnover', 'employees', 'main_customers', 'status']),
                'records' => $application->financialRecords->map(fn ($r) => ['period' => $r->period->format('Y-m'), 'inflow' => $r->inflow, 'outflow' => $r->outflow]),
                'documents' => $application->documents->map->only(['id', 'type', 'original_name', 'size', 'status', 'created_at']),
                'openRequests' => $application->informationRequests->where('status', 'open')->values()->map->only(['id', 'message', 'created_at']),
            ],
            'products' => LoanProduct::active()->get(['id', 'name', 'min_amount', 'max_amount', 'min_months', 'max_months', 'requirements']),
            'fees' => $this->rules->active()->feeTiers->map(fn ($t) => ['upTo' => $t->upper_bound, 'rate' => $t->rate]),
            'documentTypes' => self::DOCUMENT_TYPES,
        ]);
    }

    public function update(ApplicationRequest $request, LoanApplication $application)
    {
        $data = $request->validated();
        DB::transaction(function () use ($application, $data) {
            $application->update(collect($data)->except('records')->all());
            if (array_key_exists('records', $data)) {
                $application->financialRecords()->delete();
                foreach ($data['records'] ?? [] as $r) {
                    $application->financialRecords()->create(['period' => $r['period'].'-01', 'inflow' => $r['inflow'], 'outflow' => $r['outflow'], 'source' => 'business']);
                }
            }
        });

        return back()->with('success', 'Draft saved.');
    }

    public function upload(Request $request, LoanApplication $application)
    {
        $this->authorize('update', $application);
        $request->validate([
            'type' => ['required', 'in:'.implode(',', self::DOCUMENT_TYPES)],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);
        $file = $request->file('file');
        $application->documents()->create([
            'type' => $request->input('type'),
            'original_name' => substr($file->getClientOriginalName(), 0, 200),
            'path' => $file->store("applications/{$application->id}", config('banyira.documents_disk')),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function removeDocument(Request $request, LoanApplication $application, ApplicationDocument $document)
    {
        $this->authorize('update', $application);
        abort_unless($document->loan_application_id === $application->id && $document->status === 'pending', 404);
        Storage::disk(config('banyira.documents_disk'))->delete($document->path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }

    public function submit(Request $request, LoanApplication $application)
    {
        $this->authorize('update', $application);
        abort_unless($application->status === 'draft', 403);
        $this->workflow->submit($application, $request->user());

        return redirect("/business/applications/{$application->id}")->with('success', 'Application submitted. A credit officer will review it.');
    }

    public function show(Request $request, LoanApplication $application)
    {
        $this->authorize('view', $application);
        $application->load(['product:id,name', 'history', 'documents', 'informationRequests', 'listing', 'assessments' => fn ($q) => $q->whereIn('status', ['approved', 'rejected'])]);
        $decided = $application->assessments->first();

        return Inertia::render('Business/Applications/Show', [
            'application' => [
                ...$application->only(['id', 'reference', 'amount', 'term_months', 'purpose', 'status', 'risk_class', 'submitted_at', 'decided_at']),
                'statusLabel' => $application->statusLabel(),
                'product' => $application->product->name,
                'editable' => $application->isEditable(),
                'history' => $application->history->map(fn ($h) => ['to' => $h->to_status, 'label' => LoanApplication::STATUSES[$h->to_status] ?? $h->to_status, 'note' => $h->note, 'at' => $h->created_at]),
                'documents' => $application->documents->map->only(['id', 'type', 'original_name', 'status', 'created_at']),
                'requests' => $application->informationRequests->map->only(['id', 'message', 'response', 'status', 'created_at', 'responded_at']),
                'decision' => $decided ? ['outcome' => $decided->status, 'class' => $decided->final_class ?: $decided->suggested_class, 'note' => $decided->decision_note, 'at' => $decided->decided_at] : null,
                'listing' => $application->listing?->only(['reference', 'amount_requested', 'amount_accepted', 'status', 'closes_at']),
            ],
        ]);
    }

    public function respond(Request $request, InformationRequest $informationRequest)
    {
        $this->authorize('view', $informationRequest->application);
        abort_unless($informationRequest->application->business_profile_id === $request->user()->businessProfile->id, 403);
        $data = $request->validate(['response' => ['required', 'string', 'max:2000']]);
        $this->workflow->respond($informationRequest, $request->user(), $data['response']);

        return back()->with('success', 'Response sent to the credit team.');
    }

    public function destroy(Request $request, LoanApplication $application)
    {
        $this->authorize('delete', $application);
        foreach ($application->documents as $d) {
            Storage::disk(config('banyira.documents_disk'))->delete($d->path);
        }
        $application->delete();

        return redirect('/business/applications')->with('success', 'Draft deleted.');
    }
}
