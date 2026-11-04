<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Services\AgreementService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancingController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request)
    {
        $agreements = Agreement::where('borrower_id', $request->user()->id)->with('parties', 'listing:id,public_name,purpose')->latest('id')->get();

        return Inertia::render('Business/Financing', [
            'agreements' => $agreements->map(fn ($a) => [
                'id' => $a->id, 'reference' => $a->reference, 'purpose' => $a->listing->purpose, 'status' => $a->status,
                'principal' => $a->principal_total, 'feeRate' => $a->fee_rate, 'fee' => $a->service_fee, 'disbursed' => $a->disbursed_at,
                'outstanding' => $this->payments->outstanding($a),
                'investors' => $a->parties->where('role', 'investor')->count(),
                'tranches' => $a->parties->where('role', 'investor')->values()->map->only(['principal', 'annual_rate', 'term_months', 'signed_at']),
                'iSigned' => (bool) $a->parties->firstWhere(fn ($p) => $p->role === 'borrower')?->signed_at,
            ]),
        ]);
    }

    public function sign(Request $request, Agreement $agreement, AgreementService $service)
    {
        $this->authorize('pay', $agreement);
        $service->sign($agreement, $request->user());

        return back()->with('success', 'Agreement signed.');
    }

    public function repayments(Request $request)
    {
        $agreements = Agreement::where('borrower_id', $request->user()->id)->whereIn('status', ['active', 'completed'])->with('payments')->get();
        $active = $agreements->where('status', 'active');

        return Inertia::render('Business/Repayments', [
            'agreements' => $active->values()->map(fn ($a) => ['id' => $a->id, 'reference' => $a->reference, 'outstanding' => $this->payments->outstanding($a)]),
            'schedule' => BusinessController::upcoming($active->pluck('id')->all(), 60),
            'payments' => $agreements->flatMap->payments->where('purpose', 'repayment')->sortByDesc('id')->values()->map->only(['reference', 'amount', 'status', 'provider', 'is_demo', 'created_at', 'confirmed_at']),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function pay(Request $request, Agreement $agreement)
    {
        $this->authorize('pay', $agreement);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'msisdn' => ['nullable', 'regex:/^(\+?265|0)[1-9]\d{8}$/'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        $result = $this->payments->initiateRepayment($agreement, $request->user(), (string) $data['amount'], $data['idempotency_key'], $data['msisdn'] ?? null);

        return back()->with('success', $result['instructions']);
    }

    public function statement(Request $request, Agreement $agreement): StreamedResponse
    {
        $this->authorize('pay', $agreement);
        $agreement->load('schedule', 'payments.allocations');

        return response()->streamDownload(function () use ($agreement) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ["Statement for {$agreement->reference}", 'Generated '.now()->toDateTimeString()]);
            fputcsv($out, ['Due date', 'Principal due', 'Interest due', 'Principal paid', 'Interest paid', 'Status']);
            foreach ($agreement->schedule as $r) {
                fputcsv($out, [$r->due_date->toDateString(), $r->principal_due, $r->interest_due, $r->principal_paid, $r->interest_paid, $r->status]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Payment', 'Amount', 'Status', 'Confirmed']);
            foreach ($agreement->payments as $p) {
                fputcsv($out, [$p->reference, $p->amount, $p->status, $p->confirmed_at?->toDateTimeString()]);
            }
            fclose($out);
        }, "banyira-statement-{$agreement->reference}.csv", ['Content-Type' => 'text/csv']);
    }
}
