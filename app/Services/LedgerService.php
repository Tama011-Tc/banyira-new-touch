<?php

namespace App\Services;

use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Double-entry, append-only record of CONFIRMED monetary movements. Corrections are reversing entries, never edits.
 * Every entry in this build is flagged simulated: no real money moves.
 */
class LedgerService
{
    public const CLEARING = 'provider_clearing';

    public const INVESTOR_FUNDS = 'investor_funds_held';

    public const BUSINESS_DISBURSED = 'business_disbursed';

    public const FEE_REVENUE = 'fee_revenue';

    public const TAX_PAYABLE = 'tax_payable';

    public const REPAYMENTS_PAYABLE = 'investor_repayments_payable';

    public const UNAPPLIED = 'unapplied_funds';

    public const PROVIDER_FEES = 'provider_fee_expense';

    /** @param list<array{0:string,1:string,2:int,3?:string}> $lines [account, 'debit'|'credit', amount, memo] */
    public function post(string $refType, int $refId, array $lines, ?string $memo = null, ?string $reverses = null): string
    {
        $debits = array_sum(array_map(fn ($l) => $l[1] === 'debit' ? $l[2] : 0, $lines));
        $credits = array_sum(array_map(fn ($l) => $l[1] === 'credit' ? $l[2] : 0, $lines));
        if ($debits !== $credits || $debits <= 0) {
            throw new \LogicException("Unbalanced ledger posting ({$debits} debit vs {$credits} credit).");
        }
        $group = (string) Str::uuid();
        DB::transaction(function () use ($lines, $group, $refType, $refId, $memo, $reverses) {
            foreach ($lines as $l) {
                if ($l[2] === 0) {
                    continue;
                }
                LedgerEntry::create([
                    'entry_group' => $group, 'account' => $l[0], 'direction' => $l[1], 'amount' => $l[2], 'ref_type' => $refType, 'ref_id' => $refId,
                    'memo' => $l[3] ?? $memo, 'simulated' => true, 'reverses_group' => $reverses, 'created_at' => now(),
                ]);
            }
        });

        return $group;
    }

    /** Post the exact opposite of an earlier group. */
    public function reverse(string $group, string $reason): string
    {
        $orig = LedgerEntry::where('entry_group', $group)->get();
        abort_if($orig->isEmpty(), 404);
        abort_if(LedgerEntry::where('reverses_group', $group)->exists(), 422, 'Already reversed.');
        $first = $orig->first();

        return $this->post($first->ref_type ?? 'reversal', (int) $first->ref_id,
            $orig->map(fn ($e) => [$e->account, $e->direction === 'debit' ? 'credit' : 'debit', (int) $e->amount, "Reversal: {$reason}"])->all(), "Reversal: {$reason}", $group);
    }

    /** @return array<string, int> signed balance (debits - credits) per account. Whole ledger nets to zero. */
    public function trialBalance(): array
    {
        return LedgerEntry::query()->selectRaw("account, SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END) bal")->groupBy('account')->pluck('bal', 'account')->map(fn ($v) => (int) $v)->all();
    }
}
