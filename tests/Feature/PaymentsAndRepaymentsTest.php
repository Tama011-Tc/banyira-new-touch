<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ApprovalRequest;
use App\Models\Disbursement;
use App\Models\LedgerEntry;
use App\Models\PaymentAttempt;
use App\Models\PlatformFee;
use App\Models\Repayment;
use App\Models\WebhookEvent;
use App\Services\ApprovalService;
use App\Services\FeeService;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Services\Platform;
use App\Services\RepaymentService;
use App\Services\SandboxProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentsAndRepaymentsTest extends TestCase
{
    private function webhook(string $body, ?string $sig)
    {
        return $this->call('POST', '/webhooks/payments/sandbox', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => (string) $sig], $body);
    }

    private function balance(string $account): int
    {
        return app(LedgerService::class)->trialBalance()[$account] ?? 0;
    }

    // ---------- funding ----------
    public function test_funding_requires_the_exact_disclosed_amount_and_creates_an_unconfirmed_attempt(): void
    {
        $a = $this->agreement(null, null, 100000000);
        $pay = app(PaymentService::class);
        $this->assertSame(101000000, $pay->fundingAmount($a));                                 // principal + 1% investor service fee
        try {
            $pay->initiate($a->investor, $a, 'funding', 100000000, 'k1');
            $this->fail('wrong amount');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }
        $attempt = $pay->initiate($a->investor, $a, 'funding', 101000000, 'k2');
        $this->assertSame('initiated', $attempt->status);
        $this->assertTrue($attempt->simulated);
        $this->assertSame('pending_funding', $a->fresh()->status, 'initiating proves nothing');
        $this->assertSame(0, LedgerEntry::count(), 'no ledger entry before confirmation');
    }

    public function test_a_browser_return_or_unsigned_webhook_never_confirms_a_payment(): void
    {
        $a = $this->agreement();
        $pay = app(PaymentService::class);
        $attempt = $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), 'k');
        $this->actingAs($a->investor)->get("/payments/{$attempt->id}/sandbox")->assertOk();         // "return from provider"
        $this->assertSame('initiated', $attempt->fresh()->status);
        $body = json_encode(['event_id' => 'evt_forged', 'type' => 'payment.succeeded', 'ref' => $attempt->provider_ref, 'amount' => $attempt->amount]);
        $this->webhook($body, null)->assertStatus(401);
        $this->webhook($body, 'deadbeef')->assertStatus(401);
        $this->webhook($body, SandboxProvider::sign($body.' '))->assertStatus(401);
        $this->assertSame('initiated', $attempt->fresh()->status);
        $this->assertSame('pending_funding', $a->fresh()->status);
        $this->assertGreaterThanOrEqual(3, WebhookEvent::where('outcome', 'rejected')->count());     // recorded for monitoring
    }

    public function test_a_validly_signed_webhook_still_needs_the_provider_to_confirm(): void
    {
        $a = $this->agreement();
        $pay = app(PaymentService::class);
        $attempt = $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), 'k');
        // signed by us, claims success, but the provider's books say "pending"
        $body = json_encode(['event_id' => 'evt_claim', 'type' => 'payment.succeeded', 'ref' => $attempt->provider_ref, 'amount' => $attempt->amount]);
        $this->webhook($body, SandboxProvider::sign($body))->assertOk()->assertJson(['outcome' => 'unknown_payment']);
        $this->assertSame('initiated', $attempt->fresh()->status);
        $this->assertSame(0, LedgerEntry::count());
    }

    public function test_funding_posts_balanced_ledger_entries_fees_and_a_pending_disbursement(): void
    {
        $a = $this->agreement(null, null, 100000000);
        $attempt = $this->fund($a);
        $this->assertSame('succeeded', $attempt->status);
        $this->assertSame('funded', $a->fresh()->status);
        $this->assertSame(100000000, -$this->balance(LedgerService::INVESTOR_FUNDS));
        $this->assertSame(1000000, -$this->balance(LedgerService::FEE_REVENUE));                       // investor service fee
        $this->assertSame(0, array_sum(app(LedgerService::class)->trialBalance()), 'the ledger always nets to zero');
        $d = Disbursement::where('agreement_id', $a->id)->first();
        $this->assertSame('pending_approval', $d->status);
        $this->assertSame([100000000, 2000000, 98000000], [$d->gross, $d->fees, $d->net]);
        $this->assertEquals(1515000, PlatformFee::where('fee_type', 'processing')->sum('amount'));   // 1.5% of what the investor paid
        $this->assertSame('funded', \App\Models\InvestmentCommitment::first()->status);
    }

    public function test_the_same_webhook_replayed_changes_nothing(): void
    {
        $a = $this->agreement();
        $pay = app(PaymentService::class);
        $attempt = $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), 'k');
        $hook = app(SandboxProvider::class)->settle($attempt->provider_ref);
        $this->webhook($hook['body'], $hook['signature'])->assertOk()->assertJson(['outcome' => 'processed']);
        $entries = LedgerEntry::count();
        $fees = PlatformFee::count();
        for ($i = 0; $i < 3; $i++) {
            $this->webhook($hook['body'], $hook['signature'])->assertOk()->assertJson(['outcome' => 'duplicate']);
        }
        $this->assertSame([$entries, $fees], [LedgerEntry::count(), PlatformFee::count()]);
        $this->assertSame(1, Disbursement::count());
        // a second, different event for the same payment is also a no-op
        $again = json_encode(['event_id' => 'evt_other', 'type' => 'payment.succeeded', 'ref' => $attempt->provider_ref, 'amount' => $attempt->amount]);
        $this->webhook($again, SandboxProvider::sign($again))->assertJson(['outcome' => 'duplicate']);
        $this->assertSame($entries, LedgerEntry::count());
    }

    public function test_duplicate_initiation_with_one_idempotency_key_returns_one_attempt(): void
    {
        $a = $this->agreement();
        $pay = app(PaymentService::class);
        $one = $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), 'same-key');
        $two = $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), 'same-key');
        $this->assertSame($one->id, $two->id);
        $this->assertSame(1, PaymentAttempt::count());
        // and a different key while one is open is refused
        $this->expectException(ValidationException::class);
        $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), 'another-key');
    }

    public function test_someone_elses_idempotency_key_or_agreement_is_refused(): void
    {
        $a = $this->agreement();
        $pay = app(PaymentService::class);
        $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), 'key-x');
        try {
            $pay->initiate($this->user(Role::Investor), $a, 'funding', $pay->fundingAmount($a), 'key-x');
            $this->fail('should refuse');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->actingAs($this->user(Role::Investor))->post("/agreements/{$a->id}/pay", ['purpose' => 'funding', 'amount' => '1010000', 'idempotency_key' => 'z'])->assertNotFound();
    }

    public function test_failed_payments_change_nothing_and_can_be_retried(): void
    {
        $a = $this->agreement();
        $pay = app(PaymentService::class);
        $attempt = $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), 'k1');
        $hook = app(SandboxProvider::class)->settle($attempt->provider_ref, false);
        $this->assertSame('failed_recorded', $pay->handleWebhook($hook['body'], $hook['signature']));
        $this->assertSame('failed', $attempt->fresh()->status);
        $this->assertSame('pending_funding', $a->fresh()->status);
        $this->assertSame(0, LedgerEntry::count());
        $this->assertSame(0, Disbursement::count());
        $retry = $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), 'k2');
        $this->assertNotSame($attempt->id, $retry->id);
    }

    public function test_provider_amount_mismatch_fails_closed(): void
    {
        $a = $this->agreement();
        $pay = app(PaymentService::class);
        $attempt = $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), 'k');
        \App\Models\SandboxTransaction::where('ref', $attempt->provider_ref)->update(['amount' => 1]);   // provider says only 1 tambala arrived
        $hook = app(SandboxProvider::class)->settle($attempt->provider_ref);
        $this->assertSame('failed_recorded', $pay->handleWebhook($hook['body'], $hook['signature']));
        $this->assertSame('failed', $attempt->fresh()->status);
        $this->assertSame(0, LedgerEntry::count());
    }

    public function test_investor_fee_is_disclosed_before_acceptance_and_not_silently_changed_later(): void
    {
        $a = $this->agreement(null, null, 100000000);
        \App\Services\Settings::set('fees.investor_service_bp', 500);      // platform changes fees afterwards...
        $this->assertSame(101000000, app(PaymentService::class)->fundingAmount($a->fresh()));   // ...the signed agreement still uses 1%
        $this->assertSame(100, $a->fresh()->fees['investor_service_bp']);
    }

    // ---------- disbursement ----------
    public function test_disbursement_needs_finance_approval_and_is_posted_once(): void
    {
        $a = $this->agreement(null, null, 100000000);
        $this->fund($a);
        $d = Disbursement::where('agreement_id', $a->id)->first();
        $pay = app(PaymentService::class);
        foreach ([Role::Investor, Role::ItAdmin, Role::CreditApprover, Role::Sales] as $r) {
            $this->actingAs($this->user($r))->post("/finance/disbursements/{$d->id}/approve")->assertForbidden();
        }
        $this->assertSame('pending_approval', $d->fresh()->status);
        $fin = $this->user(Role::Finance);
        $this->actingAs($fin)->post("/finance/disbursements/{$d->id}/approve")->assertSessionHasNoErrors();
        $this->assertSame(['simulated', 'active'], [$d->fresh()->status, $a->fresh()->status]);
        $this->assertSame(98000000, -$this->balance(LedgerService::BUSINESS_DISBURSED));
        $this->assertSame(3000000, -$this->balance(LedgerService::FEE_REVENUE));                      // 1% investor + 2% borrower
        $this->assertSame(0, $this->balance(LedgerService::INVESTOR_FUNDS), 'investor funds fully released');
        $this->assertSame('funding', $a->application->fresh()->status, 'the campaign is only part-funded, so the application stays in funding');
        try {
            $pay->approveDisbursement($d->fresh(), $fin);
            $this->fail('second approval');
        } catch (ValidationException) {
            $this->assertSame(3000000, -$this->balance(LedgerService::FEE_REVENUE));
        }
    }

    // ---------- repayments ----------
    public function test_repayment_allocates_oldest_first_interest_before_principal(): void
    {
        $a = $this->activeLoan(120000000);
        $first = $a->schedule->sortBy('number')->first();
        $due = $first->principal_due + $first->interest_due;
        $attempt = $this->repay($a, $due);
        $r = Repayment::where('payment_attempt_id', $attempt->id)->first();
        $this->assertSame([$first->interest_due, $first->principal_due, 0], [$r->applied_interest, $r->applied_principal, $r->unapplied]);
        $this->assertSame('paid', $first->fresh()->status);
        $this->assertSame('active', $a->fresh()->status);
    }

    public function test_partial_payment_pays_interest_first_and_leaves_the_instalment_partial(): void
    {
        $a = $this->activeLoan(120000000);
        $first = $a->schedule->sortBy('number')->first();
        $this->repay($a, intdiv($first->interest_due, 2));
        $this->assertSame(['partial', intdiv($first->interest_due, 2), 0], [$first->fresh()->status, $first->fresh()->interest_paid, $first->fresh()->principal_paid]);
        $this->repay($a, $first->interest_due - intdiv($first->interest_due, 2) + 1000);
        $this->assertSame([$first->interest_due, 1000], [$first->fresh()->interest_paid, $first->fresh()->principal_paid]);
    }

    public function test_excess_on_one_payment_prepays_the_next_instalment_oldest_first(): void
    {
        $a = $this->activeLoan(120000000);
        $rows = $a->schedule->sortBy('number')->values();
        $i1 = $rows[0]->principal_due + $rows[0]->interest_due;
        $this->repay($a, $i1 + 500000);
        $this->assertSame('paid', $rows[0]->fresh()->status);
        $this->assertSame('partial', $rows[1]->fresh()->status);
        $this->assertSame(500000, $rows[1]->fresh()->interest_paid + $rows[1]->fresh()->principal_paid);
        $this->assertSame(0, $a->fresh()->unapplied, 'nothing is parked while instalments remain');
        $this->assertSame(0, array_sum(app(LedgerService::class)->trialBalance()));
        $this->assertSame($i1 + 500000, -$this->balance(LedgerService::REPAYMENTS_PAYABLE));
    }

    public function test_overpayment_beyond_the_loan_is_held_as_unapplied_in_the_ledger(): void
    {
        $a = $this->activeLoan(60000000);
        $this->repay($a, $a->total_payable + 12345);
        $this->assertSame('completed', $a->fresh()->status);
        $this->assertSame(12345, $a->fresh()->unapplied);
        $this->assertSame(12345, -$this->balance(LedgerService::UNAPPLIED));
        $this->assertSame($a->total_payable, -$this->balance(LedgerService::REPAYMENTS_PAYABLE));
        $this->assertSame(0, array_sum(app(LedgerService::class)->trialBalance()));
        $r = Repayment::first();
        $this->assertSame([$a->total_payable, 12345], [$r->applied_interest + $r->applied_principal, $r->unapplied]);
    }

    public function test_paying_everything_completes_the_loan(): void
    {
        $a = $this->activeLoan(60000000);
        $this->repay($a, $a->total_payable);
        $this->assertSame('completed', $a->fresh()->status);
        $this->assertSame('completed', $a->application->fresh()->status);
        $this->assertTrue($a->schedule()->get()->every(fn ($r) => $r->status === 'paid'));
    }

    public function test_repayment_only_by_the_borrower_on_a_live_loan(): void
    {
        $a = $this->agreement();
        $pay = app(PaymentService::class);
        $this->expectException(ValidationException::class);      // not yet funded/active
        $pay->initiate($a->business->owner, $a, 'repayment', 100000, 'k');
    }

    public function test_investor_cannot_pay_a_repayment_and_borrower_cannot_fund(): void
    {
        $a = $this->activeLoan();
        $pay = app(PaymentService::class);
        foreach ([[$a->investor, 'repayment'], [$a->business->owner, 'funding']] as [$who, $purpose]) {
            try {
                $pay->initiate($who, $a, $purpose, 100000, Str::uuid());
                $this->fail('refused');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException|ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    // ---------- overdue & default ----------
    public function test_missed_payments_make_a_loan_overdue_and_paying_clears_it(): void
    {
        $a = $this->activeLoan(60000000);
        $a->schedule()->orderBy('number')->take(2)->get()->each(fn ($r) => $r->update(['due_on' => now()->subDays(40)]));
        app(RepaymentService::class)->refreshStatus($a->fresh());
        $this->assertSame('overdue', $a->fresh()->status);
        $this->assertSame('overdue', $a->application->fresh()->status);
        $this->assertSame(2, $a->schedule()->where('status', 'overdue')->count());
        foreach ($a->schedule()->orderBy('number')->take(2)->get() as $r) {
            $this->repay($a, $r->principal_due + $r->interest_due);
        }
        $this->assertSame('active', $a->fresh()->status);
        $this->assertSame('repayment_active', $a->application->fresh()->status);
    }

    public function test_default_follows_the_contractual_definition_and_needs_a_named_approver(): void
    {
        $a = $this->activeLoan(60000000);
        $svc = app(RepaymentService::class);
        $a->schedule()->orderBy('number')->first()->update(['due_on' => now()->subDays(30)]);
        $svc->refreshStatus($a->fresh());
        $approver = $this->user(Role::CreditApprover);
        foreach ([[$approver, 'Not yet 90 days late', 'default'], [$this->user(Role::Sales), 'x', null]] as [$who, $why, $key]) {
            try {
                $svc->markDefault($a->fresh(), $who, $why);
                $this->fail('refused');
            } catch (ValidationException|\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame('overdue', $a->fresh()->status);
            }
        }
        $a->schedule()->orderBy('number')->first()->update(['due_on' => now()->subDays(95)]);
        $this->actingAs($this->user(Role::Finance))->post("/agreements/{$a->id}/default", ['reason' => 'ninety days unpaid'])->assertForbidden();
        $this->actingAs($approver)->post("/agreements/{$a->id}/default", ['reason' => 'x'])->assertSessionHasErrors('default');
        $this->actingAs($approver)->post("/agreements/{$a->id}/default", ['reason' => 'More than ninety days past due with no contact.'])->assertSessionHasNoErrors();
        $this->assertSame('defaulted', $a->fresh()->status);
        $this->assertSame('defaulted', $a->application->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'agreement.defaulted', 'actor_id' => $approver->id]);
    }

    // ---------- reversals & approvals ----------
    public function test_reversal_needs_a_second_person_and_posts_offsetting_entries(): void
    {
        $a = $this->agreement(null, null, 100000000);
        $attempt = $this->fund($a);
        $f1 = $this->user(Role::Finance);
        $f2 = $this->user(Role::Finance);
        $svc = app(ApprovalService::class);
        $req = $svc->request('reversal', ['attempt_id' => $attempt->id], 'Duplicate payment from the investor, refund requested.', $f1);
        $pay = app(PaymentService::class);
        try {
            $pay->reverse($attempt, $req, $f1);   // not approved yet
            $this->fail('needs approval');
        } catch (ValidationException) {
        }
        try {
            $svc->decide($req, $f1, true);       // cannot approve own request
            $this->fail('self-approval');
        } catch (ValidationException) {
        }
        $svc->decide($req, $f2, true);
        $pay->reverse($attempt, $req->fresh(), $f2);
        $this->assertSame('reversed', $attempt->fresh()->status);
        $this->assertSame('cancelled', $a->fresh()->status);
        $this->assertSame(0, $this->balance(LedgerService::INVESTOR_FUNDS));
        $this->assertSame(0, $this->balance(LedgerService::FEE_REVENUE));
        $this->assertSame(0, array_sum(app(LedgerService::class)->trialBalance()));
        $this->assertGreaterThan(0, LedgerEntry::whereNotNull('reverses_group')->count());
        $this->assertSame('refunded', PlatformFee::where('fee_type', 'investor_service')->first()->status);
        $this->assertSame('cancelled', \App\Models\InvestmentCommitment::first()->status);
        $this->expectException(ValidationException::class);
        $pay->reverse($attempt->fresh(), $req->fresh(), $f2);       // cannot reverse twice
    }

    public function test_funding_cannot_be_reversed_after_money_reached_the_business(): void
    {
        $a = $this->activeLoan();
        $attempt = PaymentAttempt::where('purpose', 'funding')->first();
        $svc = app(ApprovalService::class);
        $req = $svc->request('reversal', ['attempt_id' => $attempt->id], 'Reverse the funding after release.', $this->user(Role::Finance));
        $svc->decide($req, $this->user(Role::Compliance), true);
        $this->expectException(ValidationException::class);
        app(PaymentService::class)->reverse($attempt, $req->fresh(), $this->user(Role::Finance));
    }

    public function test_a_repayment_reversal_restores_the_schedule(): void
    {
        $a = $this->activeLoan(60000000);
        $first = $a->schedule->sortBy('number')->first();
        $attempt = $this->repay($a, $first->principal_due + $first->interest_due);
        $this->assertSame('paid', $first->fresh()->status);
        $svc = app(ApprovalService::class);
        $req = $svc->request('reversal', ['attempt_id' => $attempt->id], 'Payment bounced at the provider.', $this->user(Role::Finance));
        $svc->decide($req, $this->user(Role::Executive), true);
        app(PaymentService::class)->reverse($attempt, $req->fresh(), $this->user(Role::Finance));
        $this->assertSame(['scheduled', 0, 0], [$first->fresh()->status, $first->fresh()->interest_paid, $first->fresh()->principal_paid]);
        $this->assertNotNull(Repayment::first()->reversed_at);
        $this->assertSame(1, Repayment::count(), 'the repayment record is kept, marked reversed');
    }

    public function test_fee_changes_need_a_second_approver_and_do_not_touch_existing_agreements(): void
    {
        $f1 = $this->user(Role::Finance);
        $f2 = $this->user(Role::Finance);
        $a = $this->agreement();
        $this->actingAs($f1)->post('/finance/fees', ['origination_bp' => 300, 'investor_service_bp' => 100, 'processing_bp' => 150, 'tax_bp' => 0, 'reason' => 'Annual pricing review approved by the board.'])->assertSessionHasNoErrors();
        $this->assertSame(200, \App\Services\Settings::get('fees.origination_bp'));                    // not applied yet
        $req = ApprovalRequest::first();
        $this->actingAs($f1)->post("/finance/approvals/{$req->id}", ['approve' => true])->assertSessionHasErrors('approval');
        $this->actingAs($this->user(Role::ItAdmin))->post("/finance/approvals/{$req->id}", ['approve' => true])->assertForbidden();
        $this->actingAs($f2)->post("/finance/approvals/{$req->id}", ['approve' => true])->assertSessionHasNoErrors();
        $this->assertSame(300, \App\Services\Settings::get('fees.origination_bp'));
        $this->assertSame(200, $a->fresh()->fees['origination_bp']);                                     // existing agreement unchanged
        $this->assertSame(300, FeeService::snapshot()['origination_bp']);                                // new offers show the new fee
    }

    public function test_reconciliation_matches_provider_ledger_and_payment_and_flags_mismatches(): void
    {
        $a = $this->agreement();
        $attempt = $this->fund($a);
        $fin = $this->user(Role::Finance);
        $this->actingAs($fin)->post("/finance/payments/{$attempt->id}/reconcile")->assertSessionHas('success');
        $this->assertDatabaseHas('reconciliation_records', ['payment_attempt_id' => $attempt->id, 'status' => 'matched']);
        $this->actingAs($this->user(Role::Compliance))->post("/finance/payments/{$attempt->id}/reconcile")->assertForbidden();
        \App\Models\SandboxTransaction::where('ref', $attempt->provider_ref)->update(['amount' => 5]);
        $this->actingAs($fin)->post("/finance/payments/{$attempt->id}/reconcile")->assertSessionHas('error');
        $this->assertDatabaseHas('reconciliation_records', ['payment_attempt_id' => $attempt->id, 'status' => 'mismatch']);
    }

    // ---------- operating mode ----------
    public function test_live_mode_fails_closed(): void
    {
        $this->assertTrue(Platform::isSimulation());
        $exec = $this->user(Role::Executive);
        try {
            Platform::switchToLive($exec);
            $this->fail('checklist incomplete');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('mode', $e->errors());
        }
        \App\Support\ComplianceChecklist::seed();
        \App\Models\ComplianceCheck::query()->update(['status' => 'approved', 'approved_by' => $exec->id, 'approved_at' => now()]);
        $this->assertTrue(Platform::checklistComplete());
        $this->expectException(ValidationException::class);      // still refused: no live provider exists in this build
        Platform::switchToLive($exec);
    }

    public function test_money_movements_are_refused_if_live_mode_is_forced_without_approvals(): void
    {
        $a = $this->agreement();
        \App\Services\Settings::set('operating_mode', 'live');
        $this->expectException(ValidationException::class);
        app(PaymentService::class)->initiate($a->investor, $a, 'funding', app(PaymentService::class)->fundingAmount($a), 'k');
    }

    public function test_every_ledger_entry_is_flagged_simulated_and_the_ui_says_so(): void
    {
        $a = $this->activeLoan();
        $this->assertSame(0, LedgerEntry::where('simulated', false)->count());
        $this->assertTrue($a->simulated);
        $this->actingAs($this->user(Role::Finance))->get('/finance/ledger')->assertOk()->assertInertia(fn ($p) => $p->where('mode.simulation', true));
    }
}
