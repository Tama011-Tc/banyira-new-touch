<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\ApprovalRequest;
use App\Models\Expense;
use App\Models\InvestmentOffer;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Services\LedgerService;
use App\Services\MarketplaceService;
use App\Services\PaymentService;
use App\Services\RulesService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    public function test_service_fee_tier_boundaries(): void
    {
        $rules = app(RulesService::class);
        $this->assertSame('15.00', $rules->feeRate('999999.99'));
        $this->assertSame('10.00', $rules->feeRate('1000000.00'));
        $this->assertSame('10.00', $rules->feeRate('4999999.99'));
        $this->assertSame('4.00', $rules->feeRate('5000000.00'));
        $this->assertSame(['rate' => '10.00', 'fee' => '200000.00'], $rules->serviceFee('2000000'));
    }

    /** Full lifecycle: accept → sign → fund → maker-checker disbursement → repay. */
    public function test_disbursement_and_repayment_lifecycle(): void
    {
        $owner = $this->business();
        $listing = $this->listedApplication($owner, amount: '2000000', months: 12);
        $inv = [$this->investor(), $this->investor()];
        $market = app(MarketplaceService::class);
        $market->accept($market->makeOffer($listing, $inv[0], ['amount' => '1200000', 'annual_rate' => '24', 'term_months' => 12]), $owner);
        $market->accept($market->makeOffer($listing->refresh(), $inv[1], ['amount' => '800000', 'annual_rate' => '18', 'term_months' => 6]), $owner);
        $agreement = Agreement::firstOrFail();
        $this->assertSame('200000.00', $agreement->service_fee);

        // Signatures
        $this->actingAs($owner)->post("/business/agreements/{$agreement->id}/sign")->assertSessionHasNoErrors();
        foreach ($inv as $i) {
            $this->actingAs($i)->post("/investor/agreements/{$agreement->id}/sign")->assertSessionHasNoErrors();
        }
        $this->assertSame('awaiting_funding', $agreement->refresh()->status);

        // Investor funds confirmed by finance
        $maker = $this->staff('finance_officer');
        $checker = $this->staff('finance_officer');
        foreach ($agreement->parties()->where('role', 'investor')->get() as $p) {
            $this->asStaff($maker)->post("/internal/finance/parties/{$p->id}/confirm", ['provider_reference' => 'MM-'.Str::random(6)])->assertSessionHasNoErrors();
        }
        $this->assertSame('awaiting_disbursement', $agreement->refresh()->status);
        $this->assertSame(0, $agreement->investments()->count());    // accepted offers are not investments yet

        // Maker-checker
        $this->asStaff($maker)->post("/internal/finance/agreements/{$agreement->id}/disbursement")->assertSessionHasNoErrors();
        $req = ApprovalRequest::firstOrFail();
        $this->asStaff($maker)->post("/internal/finance/approvals/{$req->id}", ['approve' => true])->assertSessionHasErrors('approval');
        $this->assertSame('awaiting_disbursement', $agreement->refresh()->status);
        $this->asStaff($checker)->post("/internal/finance/approvals/{$req->id}", ['approve' => true])->assertSessionHasNoErrors();

        $agreement->refresh();
        $this->assertSame('active', $agreement->status);
        $this->assertSame(2, $agreement->investments()->count());
        foreach ($agreement->investments()->with('schedule')->get() as $investment) {
            $this->assertSame($investment->principal, Money::add(...$investment->schedule->pluck('principal_due')->all()));
        }

        // Ledger: balanced, fee is the only revenue
        $ledger = app(LedgerService::class);
        $this->assertSame('200000.00', $ledger->categoryTotal('service_charges'));
        $this->assertSame('0.00', $ledger->categoryTotal('banyira_revenue'));
        $this->assertSame('2000000.00', $ledger->categoryTotal('borrower_obligations'));
        $this->assertBalanced();

        // Repayment: pending until confirmed, idempotent, interest first
        $payments = app(PaymentService::class);
        $key = (string) Str::uuid();
        $this->actingAs($owner)->post("/business/agreements/{$agreement->id}/payments", ['amount' => '50000', 'idempotency_key' => $key])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/business/agreements/{$agreement->id}/payments", ['amount' => '50000', 'idempotency_key' => $key]);
        $this->assertSame(1, Payment::where('purpose', 'repayment')->count());
        $payment = Payment::where('purpose', 'repayment')->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('0.00', $ledger->categoryTotal('interest_repayments'));

        $payments->confirm($payment, $maker, 'MM-REPAY-1');
        $payments->confirm($payment->refresh(), $maker, 'MM-REPAY-1');           // second confirmation changes nothing
        $this->assertSame(1, JournalEntry::where('source_type', 'payment')->where('source_id', $payment->id)->where('description', 'like', 'Repayment%')->count());

        $alloc = $payment->allocations()->get();
        $this->assertSame('50000.00', Money::add(...$alloc->map(fn ($a) => Money::add($a->principal, $a->interest))->all()));
        $firstDue = $agreement->schedule()->orderBy('due_date')->get()->groupBy(fn ($r) => $r->due_date->toDateString())->first();
        $this->assertSame(Money::add(...$firstDue->pluck('interest_due')->all()), $firstDue->map->fresh()->reduce(fn ($c, $r) => Money::add($c, $r->interest_paid), '0'));
        $this->assertBalanced();
        $this->assertSame('200000.00', $ledger->categoryTotal('service_charges'));   // repayments never touch revenue
    }

    public function test_overpayment_is_refused(): void
    {
        $owner = $this->business();
        $listing = $this->listedApplication($owner, amount: '1000000');
        $market = app(MarketplaceService::class);
        $market->accept($market->makeOffer($listing, $this->investor(), ['amount' => '1000000', 'annual_rate' => '20', 'term_months' => 6]), $owner);
        $agreement = Agreement::firstOrFail();
        $agreement->update(['status' => 'active']);
        $this->actingAs($owner)->post("/business/agreements/{$agreement->id}/payments", ['amount' => '100', 'idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('amount');   // no schedule yet, nothing outstanding
    }

    public function test_ledger_rejects_unbalanced_entries_and_is_immutable(): void
    {
        $ledger = app(LedgerService::class);
        try {
            $ledger->post('Bad', [['1010', '100', '0'], ['5090', '0', '99']]);
            $this->fail('Unbalanced entry was accepted.');
        } catch (InvalidArgumentException) {
        }

        $e = $ledger->post('Good', [['5090', '100', '0'], ['1010', '0', '100']]);
        $this->expectException(\LogicException::class);
        $e->update(['description' => 'Edited']);
    }

    public function test_expense_maker_checker_and_reversal(): void
    {
        $maker = $this->staff('finance_officer');
        $checker = $this->staff('finance_officer');
        $this->asStaff($maker)->post('/internal/finance/expenses', ['category' => 'Premises', 'description' => 'Rent', 'amount' => '400000', 'incurred_on' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame('0.00', app(LedgerService::class)->categoryTotal('operating_expenses'));

        $req = ApprovalRequest::firstOrFail();
        $this->asStaff($checker)->post("/internal/finance/approvals/{$req->id}", ['approve' => true])->assertSessionHasNoErrors();
        $expense = Expense::firstOrFail();
        $this->assertSame('posted', $expense->status);
        $this->assertSame('400000.00', app(LedgerService::class)->categoryTotal('operating_expenses'));

        $this->asStaff($maker)->post("/internal/finance/ledger/{$expense->journal_entry_id}/reverse", ['reason' => 'Duplicate'])->assertSessionHasNoErrors();
        $this->assertSame('0.00', app(LedgerService::class)->categoryTotal('operating_expenses'));
        $this->assertSame(2, JournalEntry::count());
        $this->asStaff($maker)->post("/internal/finance/ledger/{$expense->journal_entry_id}/reverse", ['reason' => 'Again'])->assertStatus(500);
    }

    public function test_unsigned_payment_webhooks_are_rejected(): void
    {
        $this->postJson('/webhooks/payments', ['reference' => 'x', 'status' => 'SUCCESS', 'amount' => '100'])->assertStatus(400);
        $this->assertDatabaseHas('audit_logs', ['area' => 'payments']);
    }

    private function assertBalanced(): void
    {
        $row = DB::table('journal_lines')->selectRaw('SUM(debit) d, SUM(credit) c')->first();
        $this->assertSame(Money::of($row->d), Money::of($row->c), 'Ledger does not balance');
    }
}
