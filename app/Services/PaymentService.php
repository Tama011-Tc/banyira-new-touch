<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Agreement;
use App\Models\Investment;
use App\Models\Payment;
use App\Models\RepaymentSchedule;
use App\Models\User;
use App\Notifications\Alert;
use App\Support\Money;
use App\Support\Reference;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Repayments. A payment is "pending" until the provider confirms it by signed
 * webhook or finance confirms it through reconciliation. Confirmation is
 * idempotent: confirming the same payment twice changes nothing.
 */
class PaymentService
{
    public function __construct(private PaymentGateway $gateway, private LedgerService $ledger, private AuditLogger $audit) {}

    public function outstanding(Agreement $agreement): string
    {
        $row = $agreement->schedule()->toBase()->selectRaw('COALESCE(SUM(principal_due + interest_due - principal_paid - interest_paid), 0) t')->first();

        return Money::of($row->t);
    }

    /** @return array{payment: Payment, instructions: string} */
    public function initiateRepayment(Agreement $agreement, User $payer, string $amount, string $idempotencyKey, ?string $msisdn = null): array
    {
        if ($existing = Payment::where('idempotency_key', $idempotencyKey)->first()) {
            return ['payment' => $existing, 'instructions' => 'This payment was already started. Reference '.$existing->reference.'.'];
        }
        if ($agreement->status !== 'active') {
            throw ValidationException::withMessages(['amount' => 'Repayments can only be made on active financing.']);
        }
        $amount = Money::of($amount);
        if (Money::cmp($amount, '0') <= 0 || Money::cmp($amount, $this->outstanding($agreement)) > 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount up to the outstanding balance of '.Money::format($this->outstanding($agreement)).'.']);
        }

        $payment = Payment::create([
            'reference' => Reference::make('PAY'),
            'idempotency_key' => $idempotencyKey,
            'agreement_id' => $agreement->id,
            'payer_id' => $payer->id,
            'direction' => 'in',
            'purpose' => 'repayment',
            'amount' => $amount,
            'provider' => $this->gateway->name(),
            'payer_msisdn' => $msisdn,
            'status' => 'pending',
            'is_demo' => $this->gateway->name() === 'demo',
        ]);
        $result = $this->gateway->requestCollection($payment);
        if ($result['provider_reference']) {
            $payment->update(['provider_reference' => $result['provider_reference']]);
        }

        return ['payment' => $payment, 'instructions' => $result['instructions']];
    }

    public function confirm(Payment $payment, ?User $by, ?string $providerReference = null): Payment
    {
        $done = DB::transaction(function () use ($payment, $by, $providerReference) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->status === 'confirmed') {
                return false;                                      // idempotent
            }
            if ($payment->status !== 'pending' || $payment->purpose !== 'repayment') {
                throw ValidationException::withMessages(['payment' => 'Only pending repayments can be confirmed.']);
            }

            [$principal, $interest] = $this->allocate($payment);
            $remainder = Money::sub($payment->amount, Money::add($principal, $interest));
            $lines = [['1020', $payment->amount, '0', 'Received into client account']];
            if (! Money::isZero($principal)) {
                $lines[] = ['2020', '0', $principal, 'Principal due to investors'];
            }
            if (! Money::isZero($interest)) {
                $lines[] = ['2030', '0', $interest, 'Interest due to investors'];
            }
            if (! Money::isZero($remainder)) {
                $lines[] = ['2040', '0', $remainder, 'Unallocated'];
            }
            $this->ledger->post("Repayment {$payment->reference} on {$payment->agreement->reference}", $lines, $payment, $by, $by);
            if (! Money::isZero($principal)) {
                $this->ledger->post("Borrower principal reduced by {$payment->reference}", [['2110', $principal, '0'], ['1210', '0', $principal]], $payment, $by, $by);
            }

            $payment->update(['status' => 'confirmed', 'confirmed_at' => now(), 'confirmed_by' => $by?->id, 'provider_reference' => $payment->provider_reference ?? $providerReference]);
            $this->audit->log('finance', "Confirmed repayment {$payment->reference}", $payment, ['principal' => $principal, 'interest' => $interest], $by);
            $this->completeIfRepaid($payment->agreement);

            return true;
        });

        $payment->refresh();
        if ($done) {
            $payment->payer?->notify(new Alert('Payment confirmed', Money::format($payment->amount)." received for {$payment->agreement->reference}. Thank you.", '/business/repayments', true));
            $payment->allocations()->with('schedule.investment.investor')->get()
                ->groupBy(fn ($a) => $a->schedule->investment->investor_id)
                ->each(fn ($rows) => $rows->first()->schedule->investment->investor->notify(new Alert(
                    'Repayment received', Money::format(Money::add(...$rows->map(fn ($r) => Money::add($r->principal, $r->interest))->all())).' credited to your investment.', '/investor/repayments', true,
                )));
        }

        return $payment;
    }

    public function fail(Payment $payment, ?User $by): void
    {
        if ($payment->status === 'pending') {
            $payment->update(['status' => 'failed']);
            $this->audit->log('finance', "Marked payment {$payment->reference} as failed", $payment, [], $by);
        }
    }

    /** Provider callback. Unknown, mismatched or unsigned callbacks are ignored. */
    public function handleWebhook(array $data): ?Payment
    {
        $payment = Payment::where('idempotency_key', $data['idempotency_key'])->first();
        if (! $payment || Money::cmp($payment->amount, $data['amount']) !== 0) {
            return null;
        }
        if ($data['status'] === 'failed') {
            $this->fail($payment, null);

            return $payment;
        }

        return $this->confirm($payment, null, $data['provider_reference']);
    }

    /**
     * Oldest due date first. Within a due date, interest is paid before
     * principal, and a shortfall is shared pro rata between investors so no
     * investor is paid ahead of another on the same instalment date.
     */
    private function allocate(Payment $payment): array
    {
        $left = $payment->amount;
        $totalP = '0';
        $totalI = '0';
        $groups = RepaymentSchedule::where('agreement_id', $payment->agreement_id)
            ->whereIn('status', ['scheduled', 'partial', 'overdue'])
            ->orderBy('due_date')->orderBy('investment_id')
            ->lockForUpdate()->get()
            ->groupBy(fn ($r) => $r->due_date->toDateString());

        foreach ($groups as $rows) {
            if (Money::isZero($left)) {
                break;
            }
            $rows = $rows->values();
            $payI = $this->share($left, $rows->map(fn ($r) => Money::sub($r->interest_due, $r->interest_paid))->all());
            $left = Money::sub($left, Money::add('0', ...$payI));
            $payP = $this->share($left, $rows->map(fn ($r) => Money::sub($r->principal_due, $r->principal_paid))->all());
            $left = Money::sub($left, Money::add('0', ...$payP));

            foreach ($rows as $i => $row) {
                if (Money::isZero(Money::add($payI[$i], $payP[$i]))) {
                    continue;
                }
                $row->interest_paid = Money::add($row->interest_paid, $payI[$i]);
                $row->principal_paid = Money::add($row->principal_paid, $payP[$i]);
                $row->status = Money::isZero($row->amountOutstanding()) ? 'paid' : 'partial';
                $row->save();
                $payment->allocations()->create(['repayment_schedule_id' => $row->id, 'principal' => $payP[$i], 'interest' => $payI[$i]]);
                Investment::whereKey($row->investment_id)->update([
                    'principal_repaid' => DB::raw('principal_repaid + '.$payP[$i]),
                    'interest_received' => DB::raw('interest_received + '.$payI[$i]),
                ]);
                $totalP = Money::add($totalP, $payP[$i]);
                $totalI = Money::add($totalI, $payI[$i]);
            }
        }

        return [$totalP, $totalI];
    }

    /**
     * Split $available across $owed amounts: everything owed if it is enough,
     * otherwise pro rata, with rounding remainders given to the last share.
     *
     * @param  list<string>  $owed
     * @return list<string>
     */
    private function share(string $available, array $owed): array
    {
        $total = Money::add('0', ...$owed);
        if (Money::cmp($available, $total) >= 0 || Money::isZero($total)) {
            return $owed;
        }
        $out = [];
        $given = '0';
        foreach ($owed as $i => $o) {
            $part = $i === array_key_last($owed) ? Money::sub($available, $given) : Money::of(bcdiv(bcmul($available, $o, 12), $total, 12));
            $part = Money::min($part, $o);
            $out[] = $part;
            $given = Money::add($given, $part);
        }

        return $out;
    }

    private function completeIfRepaid(Agreement $agreement): void
    {
        foreach ($agreement->investments()->get() as $inv) {
            $open = $inv->schedule()->where('status', '!=', 'paid')->exists();
            $inv->update(['status' => $open ? ($inv->schedule()->where('status', 'overdue')->exists() ? 'in_arrears' : 'active') : 'completed']);
        }
        if (! $agreement->schedule()->where('status', '!=', 'paid')->exists()) {
            $agreement->update(['status' => 'completed']);
        }
    }

    /** Flag missed instalments. Scheduled daily. */
    public function markOverdue(): int
    {
        $n = RepaymentSchedule::whereIn('status', ['scheduled', 'partial'])->whereDate('due_date', '<', now())->update(['status' => 'overdue']);
        Investment::where('status', 'active')->whereHas('schedule', fn ($q) => $q->where('status', 'overdue'))->update(['status' => 'in_arrears']);

        return $n;
    }
}
