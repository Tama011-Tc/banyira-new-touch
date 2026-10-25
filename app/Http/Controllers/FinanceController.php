<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Disbursement;
use App\Models\LedgerEntry;
use App\Models\PaymentAttempt;
use App\Models\PlatformFee;
use App\Models\ReconciliationRecord;
use App\Services\ApprovalService;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Services\Settings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceController extends Controller
{
    public function index(LedgerService $ledger)
    {
        $tb = $ledger->trialBalance();

        return Inertia::render('finance/Index', [
            'trial_balance' => collect($tb)->map(fn ($v, $k) => ['account' => $k, 'balance' => $v])->values(),
            'trial_total' => array_sum($tb),
            'fees' => PlatformFee::query()->selectRaw('fee_type, payer, status, COUNT(*) n, SUM(amount) amount, SUM(tax) tax')->groupBy('fee_type', 'payer', 'status')->get(),
            'payments' => PaymentAttempt::with('user:id,name')->latest('id')->limit(25)->get()->map(fn ($p) => ['id' => $p->id, 'purpose' => $p->purpose, 'amount' => $p->amount, 'status' => $p->status, 'ref' => $p->provider_ref, 'who' => $p->user->name, 'simulated' => $p->simulated, 'reconciled' => ReconciliationRecord::where('payment_attempt_id', $p->id)->latest('id')->value('status')]),
            'disbursements' => Disbursement::with('agreement.business')->where('status', 'pending_approval')->get()->map(fn ($d) => ['id' => $d->id, 'agreement_id' => $d->agreement_id, 'business' => $d->agreement->business->display_name, 'gross' => $d->gross, 'fees' => $d->fees, 'net' => $d->net]),
            'approvals' => ApprovalRequest::with('requester:id,name')->latest('id')->limit(20)->get()->map(fn ($r) => ['id' => $r->id, 'type' => $r->type, 'reason' => $r->reason, 'payload' => $r->payload, 'status' => $r->status, 'by' => $r->requester->name, 'mine' => $r->requested_by === auth()->id()]),
            'fee_settings' => ['fees.origination_bp' => Settings::get('fees.origination_bp'), 'fees.investor_service_bp' => Settings::get('fees.investor_service_bp'), 'fees.processing_bp' => Settings::get('fees.processing_bp'), 'fees.tax_bp' => Settings::get('fees.tax_bp')],
        ]);
    }

    public function approveDisbursement(Request $request, Disbursement $disbursement, PaymentService $payments)
    {
        $payments->approveDisbursement($disbursement, $request->user());

        return back()->with('success', 'Disbursement approved (simulated).');
    }

    /** Reconcile = compare the provider's own record with our ledger for that payment. */
    public function reconcile(Request $request, PaymentAttempt $payment, \App\Services\SandboxProvider $provider)
    {
        $truth = $provider->verify($payment->provider_ref);
        $ledger = (int) LedgerEntry::where('ref_type', 'payment_attempt')->where('ref_id', $payment->id)->where('account', LedgerService::CLEARING)->where('direction', 'debit')->sum('amount')
            - (int) LedgerEntry::where('ref_type', 'payment_attempt')->where('ref_id', $payment->id)->where('account', LedgerService::CLEARING)->where('direction', 'credit')->whereNull('reverses_group')->where('memo', '!=', 'Provider processing fee')->sum('amount');
        $providerAmount = ($truth && $truth['status'] === 'succeeded' && $payment->status === 'succeeded') ? $truth['amount'] : 0;
        $expected = $payment->status === 'succeeded' ? $payment->amount : 0;
        $matched = $providerAmount === $expected && $ledger === $expected;
        ReconciliationRecord::create(['payment_attempt_id' => $payment->id, 'status' => $matched ? 'matched' : 'mismatch', 'provider_amount' => $providerAmount, 'ledger_amount' => $ledger, 'checked_by' => $request->user()->id, 'created_at' => now()]);
        app(\App\Services\AuditLogger::class)->record('payment.reconciled', $payment, ['matched' => $matched]);

        return back()->with($matched ? 'success' : 'error', $matched ? 'Matched: provider, ledger and payment agree.' : 'Mismatch recorded for investigation.');
    }

    public function requestFeeChange(Request $request, ApprovalService $approvals)
    {
        $d = $request->validate(['origination_bp' => 'required|integer|min:0|max:1000', 'investor_service_bp' => 'required|integer|min:0|max:1000', 'processing_bp' => 'required|integer|min:0|max:500', 'tax_bp' => 'required|integer|min:0|max:3000', 'reason' => 'required|string|max:500']);
        $approvals->request('fee_change', ['settings' => ['fees.origination_bp' => $d['origination_bp'], 'fees.investor_service_bp' => $d['investor_service_bp'], 'fees.processing_bp' => $d['processing_bp'], 'fees.tax_bp' => $d['tax_bp']]], $d['reason'], $request->user());

        return back()->with('success', 'Fee change requested. A different finance, compliance or executive user must approve it. It never affects offers already made.');
    }

    public function requestReversal(Request $request, PaymentAttempt $payment, ApprovalService $approvals)
    {
        $approvals->request('reversal', ['attempt_id' => $payment->id], (string) $request->input('reason'), $request->user());

        return back()->with('success', 'Reversal requested; a second person must approve it.');
    }

    public function decideApproval(Request $request, ApprovalRequest $approval, ApprovalService $approvals)
    {
        $approvals->decide($approval, $request->user(), $request->boolean('approve'));

        return back()->with('success', 'Recorded.');
    }

    public function executeReversal(Request $request, ApprovalRequest $approval, PaymentService $payments)
    {
        abort_unless($approval->type === 'reversal', 404);
        $payments->reverse(PaymentAttempt::findOrFail($approval->payload['attempt_id']), $approval, $request->user());

        return back()->with('success', 'Payment reversed with offsetting ledger entries.');
    }

    public function ledger(Request $request)
    {
        return Inertia::render('finance/Ledger', ['entries' => LedgerEntry::query()->orderByDesc('id')->paginate(40)->through(fn ($e) => ['id' => $e->id, 'group' => substr($e->entry_group, 0, 8), 'account' => $e->account, 'direction' => $e->direction, 'amount' => $e->amount, 'memo' => $e->memo, 'at' => $e->created_at->toDateTimeString(), 'simulated' => $e->simulated, 'reversal' => (bool) $e->reverses_group])]);
    }
}
