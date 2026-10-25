<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\Repayment;
use App\Models\SmsMessage;
use App\Services\LedgerService;
use App\Services\PayoutService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Business plan: Banyira manages the repayments back to each investor using the bank details the investor provides. */
class PayoutsTest extends TestCase
{
    private function bal(string $account): int
    {
        return app(LedgerService::class)->trialBalance()[$account] ?? 0;
    }

    public function test_an_investor_cannot_fund_a_loan_without_bank_details(): void
    {
        $inv = $this->user(Role::Investor);
        $inv->investorProfile->update(['payout_bank' => null, 'payout_account_name' => null, 'payout_account_number' => null]);
        $a = $this->agreement(null, $inv->fresh());
        try {
            app(PaymentService::class)->initiate($inv->fresh(), $a, 'funding', app(PaymentService::class)->fundingAmount($a), 'k');
            $this->fail('needs bank details');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payout', $e->errors());
        }
    }

    public function test_details_are_validated_encrypted_masked_audited_and_need_the_password(): void
    {
        $inv = $this->user(Role::Investor);
        $body = ['payout_bank' => 'Standard Bank', 'payout_account_name' => 'Jane Banda', 'payout_account_number' => '9876543210', 'current_password' => 'password'];
        $this->actingAs($inv)->put('/investor/payout', ['current_password' => 'wrong'] + $body)->assertSessionHasErrors('current_password');
        $this->actingAs($inv)->put('/investor/payout', ['payout_account_number' => '12ab'] + $body)->assertSessionHasErrors('payout_account_number');
        $this->actingAs($inv)->put('/investor/payout', $body)->assertSessionHasNoErrors();

        $raw = DB::table('investor_profiles')->where('user_id', $inv->id)->value('payout_account_number');
        $this->assertStringNotContainsString('9876543210', $raw, 'encrypted at rest');
        $this->assertSame('9876543210', $inv->investorProfile->fresh()->payout_account_number);
        $page = $this->actingAs($inv)->get('/investor/profile')->assertOk();
        $this->assertStringNotContainsString('9876543210', $page->getContent(), 'the full number never reaches the browser');
        $page->assertInertia(fn ($p) => $p->where('profile.payout_account_masked', '••••3210'));
        $log = DB::table('audit_logs')->where('action', 'investor.payout_details_changed')->first();
        $this->assertStringNotContainsString('9876543210', $log->meta);
        $this->assertNotNull($inv->investorProfile->fresh()->payout_details_changed_at);
    }

    public function test_a_confirmed_repayment_queues_exactly_one_payout_for_the_amount_applied(): void
    {
        $a = $this->activeLoan(60000000);
        $first = $a->schedule->sortBy('number')->first();
        $attempt = $this->repay($a, $first->principal_due + $first->interest_due);
        $p = Payout::first();
        $this->assertSame([$a->investor_id, $first->principal_due + $first->interest_due, 'pending'], [$p->investor_id, $p->amount, $p->status]);
        $this->assertSame(Repayment::where('payment_attempt_id', $attempt->id)->value('id'), $p->repayment_id);
        // replaying the provider's event cannot create another
        $hook = app(\App\Services\SandboxProvider::class)->settle($attempt->provider_ref);
        app(PaymentService::class)->handleWebhook($hook['body'], $hook['signature']);
        $this->assertSame(1, Payout::count());
    }

    public function test_overpaid_money_that_was_not_applied_is_not_paid_out(): void
    {
        $a = $this->activeLoan(60000000);
        $this->repay($a, $a->total_payable + 12345);
        $this->assertSame($a->total_payable, (int) Payout::sum('amount'), 'the excess is held for the borrower, not sent to the investor');
    }

    public function test_finance_releases_a_payout_once_and_the_ledger_stays_balanced(): void
    {
        $a = $this->activeLoan(60000000);
        $r = $a->schedule->sortBy('number')->first();
        $this->repay($a, $r->principal_due + $r->interest_due);
        $p = Payout::first();
        foreach ([Role::Investor, Role::ItAdmin, Role::CreditApprover, Role::Sales] as $role) {
            $this->actingAs($this->user($role))->post("/finance/payouts/{$p->id}/release")->assertForbidden();
        }
        $this->assertSame('pending', $p->fresh()->status);
        $before = $this->bal(LedgerService::REPAYMENTS_PAYABLE);
        $fin = $this->user(Role::Finance);
        $this->actingAs($fin)->post("/finance/payouts/{$p->id}/release")->assertSessionHasNoErrors();
        $this->assertSame(['released', $fin->id], [$p->fresh()->status, $p->fresh()->released_by]);
        $this->assertSame($before + $p->amount, $this->bal(LedgerService::REPAYMENTS_PAYABLE), 'the owed balance is cleared');
        $this->assertSame(0, array_sum(app(LedgerService::class)->trialBalance()));
        $this->actingAs($fin)->post("/finance/payouts/{$p->id}/release")->assertSessionHasErrors('payout');   // not twice
        $this->assertSame(1, LedgerEntry::where('ref_type', 'payout')->where('direction', 'debit')->count());
        $audit = DB::table('audit_logs')->where('action', 'payout.released')->first();
        $this->assertStringContainsString('••••', $audit->meta);
        $this->assertStringNotContainsString('1002003004', $audit->meta);
    }

    public function test_finance_sees_masked_accounts_and_a_warning_for_freshly_changed_details(): void
    {
        $a = $this->activeLoan(60000000);
        $r = $a->schedule->sortBy('number')->first();
        $this->repay($a, $r->principal_due + $r->interest_due);
        $a->investor->investorProfile->update(['payout_details_changed_at' => now()]);
        $res = $this->actingAs($this->user(Role::Finance))->get('/finance')->assertOk();
        $this->assertStringNotContainsString('1002003004', $res->getContent());
        $res->assertInertia(fn ($p) => $p->where('payouts.0.account', '••••3004')->where('payouts.0.details_recent', true)->where('payouts.0.ready', true));
        $this->actingAs($this->user(Role::ItAdmin))->get('/finance')->assertInertia(fn ($p) => $p->where('payouts.0.account', '••••3004'));
    }

    public function test_a_payout_cannot_be_released_without_bank_details(): void
    {
        $a = $this->activeLoan(60000000);
        $r = $a->schedule->sortBy('number')->first();
        $this->repay($a, $r->principal_due + $r->interest_due);
        $a->investor->investorProfile->update(['payout_account_number' => null]);
        $this->expectException(ValidationException::class);
        app(PayoutService::class)->release(Payout::first(), $this->user(Role::Finance));
    }

    public function test_reversing_a_repayment_cancels_an_unreleased_payout_but_not_a_released_one(): void
    {
        $a = $this->activeLoan(60000000);
        $rows = $a->schedule->sortBy('number')->values();
        $one = $this->repay($a, $rows[0]->principal_due + $rows[0]->interest_due);
        $two = $this->repay($a, $rows[1]->principal_due + $rows[1]->interest_due);
        $svc = app(\App\Services\ApprovalService::class);
        $f1 = $this->user(Role::Finance);
        $f2 = $this->user(Role::Compliance);

        $req = $svc->request('reversal', ['attempt_id' => $one->id], 'Payment bounced at the provider.', $f1);
        $svc->decide($req, $f2, true);
        app(PaymentService::class)->reverse($one, $req->fresh(), $f1);
        $this->assertSame('cancelled', Payout::where('repayment_id', Repayment::where('payment_attempt_id', $one->id)->value('id'))->value('status'));

        app(PayoutService::class)->release(Payout::where('status', 'pending')->first(), $f1);                       // the second is paid out
        $req2 = $svc->request('reversal', ['attempt_id' => $two->id], 'Trying to reverse after paying out.', $f1);
        $svc->decide($req2, $f2, true);
        try {
            app(PaymentService::class)->reverse($two, $req2->fresh(), $f1);
            $this->fail('already paid out');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('already been paid out', $e->errors()['payment'][0]);
        }
        $this->assertSame('succeeded', $two->fresh()->status);
    }

    public function test_the_portfolio_separates_collected_from_paid_out(): void
    {
        $a = $this->activeLoan(60000000);
        $rows = $a->schedule->sortBy('number')->values();
        $this->repay($a, $rows[0]->principal_due + $rows[0]->interest_due);
        $this->repay($a, $rows[1]->principal_due + $rows[1]->interest_due);
        app(PayoutService::class)->release(Payout::orderBy('id')->first(), $this->user(Role::Finance));
        $this->actingAs($a->investor)->get('/portfolio')->assertInertia(fn ($p) => $p->where('summary.paid_out', $rows[0]->principal_due + $rows[0]->interest_due)->where('summary.awaiting_payout', $rows[1]->principal_due + $rows[1]->interest_due));
        $this->actingAs($this->user(Role::Investor))->get('/portfolio')->assertInertia(fn ($p) => $p->where('summary.paid_out', 0));
    }

    public function test_executive_sees_progress_against_the_plans_sme_target(): void
    {
        $this->activeLoan(60000000);
        $this->actingAs($this->user(Role::Executive))->get('/executive')->assertInertia(fn ($p) => $p->where('report.target.smes_financed', 1)->where('report.target.goal', 1000));
    }

    public function test_the_licensing_item_from_the_plan_is_on_the_checklist(): void
    {
        \App\Support\ComplianceChecklist::seed();
        $this->assertDatabaseHas('compliance_checks', ['key' => 'mfi_licence']);
        $this->assertStringContainsString('Microfinance Act', \App\Models\ComplianceCheck::where('key', 'mfi_licence')->value('description'));
    }
}
