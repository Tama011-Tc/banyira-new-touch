<?php

namespace App\Http\Controllers;

use App\Models\LoanAgreement;
use App\Models\PaymentAttempt;
use App\Services\PaymentService;
use App\Services\RepaymentService;
use App\Services\SandboxProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AgreementController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    /** Parties see their own agreements; finance/compliance/executive see all. Others get a 404. */
    private function visible(Request $r)
    {
        $u = $r->user();
        $q = LoanAgreement::query();
        if ($u->role->can('agreements.view_any') || $u->role->can('credit.approve')) {
            return $q;
        }

        return $q->where(fn ($q) => $q->where('investor_id', $u->id)->orWhereIn('business_id', \App\Models\Business::where('owner_id', $u->id)->select('id')));
    }

    public function index(Request $request)
    {
        $rows = $this->visible($request)->with(['business', 'investor'])->latest('id')->get();

        return Inertia::render('agreements/Index', ['agreements' => $rows->map(fn ($a) => ['id' => $a->id, 'business' => $a->business->display_name, 'investor' => $request->user()->role->value === 'investor' ? null : $a->investor->name, 'principal' => $a->principal, 'status' => $a->status, 'rate_bp' => $a->rate_bp, 'term_months' => $a->term_months, 'simulated' => $a->simulated])]);
    }

    public function show(Request $request, int $agreement)
    {
        $a = $this->visible($request)->with(['business', 'investor', 'schedule', 'disbursement', 'repayments', 'listing'])->findOrFail($agreement);
        $u = $request->user();
        $u->role->can('oversight.read_only') && app(\App\Services\AuditLogger::class)->record('oversight.viewed', $a, ['page' => 'agreement']);
        $isInvestor = $a->investor_id === $u->id;
        $isBorrower = $a->business->owner_id === $u->id;
        $f = \App\Services\FeeService::onPrincipal($a->fees, $a->principal);
        $paid = $a->schedule->sum(fn ($s) => $s->principal_paid + $s->interest_paid);

        return Inertia::render('agreements/Show', [
            'agreement' => [
                'id' => $a->id, 'status' => $a->status, 'simulated' => $a->simulated, 'principal' => $a->principal, 'rate_bp' => $a->rate_bp, 'term_months' => $a->term_months, 'frequency' => $a->frequency, 'method' => $a->interest_method, 'grace' => $a->grace_instalments,
                'total_interest' => $a->total_interest, 'total_payable' => $a->total_payable, 'unapplied' => $a->unapplied, 'hash' => substr($a->agreement_hash, 0, 12), 'business' => $a->business->display_name, 'investor' => $isInvestor || ! $u->role->can('agreements.view_any') && ! $isBorrower ? $a->investor->name : ($isBorrower ? 'Investor' : $a->investor->name),
                'fees' => $f, 'fee_rates' => $a->fees, 'funding_amount' => $f['investor_pays'], 'outstanding' => $a->total_payable - $paid, 'paid' => $paid,
                'disbursement' => $a->disbursement ? ['id' => $a->disbursement->id, 'status' => $a->disbursement->status, 'net' => $a->disbursement->net, 'fees' => $a->disbursement->fees] : null,
                'schedule' => $a->schedule->map(fn ($s) => ['number' => $s->number, 'due_on' => $s->due_on->toDateString(), 'principal_due' => $s->principal_due, 'interest_due' => $s->interest_due, 'principal_paid' => $s->principal_paid, 'interest_paid' => $s->interest_paid, 'status' => $s->status]),
                'repayments' => $a->repayments->map(fn ($r) => ['id' => $r->id, 'amount' => $r->amount, 'interest' => $r->applied_interest, 'principal' => $r->applied_principal, 'unapplied' => $r->unapplied, 'at' => $r->received_at->toDateTimeString(), 'reversed' => (bool) $r->reversed_at]),
            ],
            'actions' => [
                'can_fund' => $isInvestor && $a->status === 'pending_funding', 'can_repay' => $isBorrower && in_array($a->status, ['active', 'overdue', 'restructured']),
                'can_approve_disbursement' => $a->disbursement?->status === 'pending_approval' && $u->role->can('finance.approve'),
                'can_default' => $u->role->can('credit.approve') && in_array($a->status, ['overdue', 'restructured']),
            ],
            'pending_payment' => PaymentAttempt::where('agreement_id', $a->id)->where('status', 'initiated')->where('user_id', $u->id)->latest('id')->first()?->only('id', 'purpose', 'amount'),
        ]);
    }

    public function pay(Request $request, int $agreement)
    {
        $a = $this->visible($request)->findOrFail($agreement);
        $d = $request->validate(['purpose' => 'required|in:funding,repayment', 'amount' => ['required', \App\Support\Money::DECIMAL_RULE], 'idempotency_key' => 'required|string|max:64']);
        $attempt = $this->payments->initiate($request->user(), $a, $d['purpose'], \App\Support\Money::toMinor($d['amount']), $d['idempotency_key']);

        return to_route('payments.sandbox', $attempt);
    }

    /** The sandbox "provider" page. Clearly simulated; returning from it proves nothing: status comes from the webhook + provider check. */
    public function sandbox(Request $request, PaymentAttempt $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 404);

        return Inertia::render('payments/Sandbox', ['payment' => $payment->only('id', 'purpose', 'amount', 'status', 'provider_ref', 'failure_reason') + ['agreement_id' => $payment->agreement_id]]);
    }

    public function settle(Request $request, PaymentAttempt $payment, SandboxProvider $provider)
    {
        abort_unless($payment->user_id === $request->user()->id && $payment->simulated, 404);
        $result = $request->boolean('succeed', true);
        $hook = $provider->settle($payment->provider_ref, $result);
        $this->payments->handleWebhook($hook['body'], $hook['signature']);   // the provider "calls us back"

        return to_route('payments.sandbox', $payment)->with($result ? 'success' : 'error', $result ? 'Sandbox payment authorised. The platform verified it server-side.' : 'Sandbox payment declined.');
    }
}
