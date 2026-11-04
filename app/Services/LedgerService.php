<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Support\Money;
use App\Support\Reference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Double-entry ledger. Every entry must balance; entries are never edited or
 * deleted. Account categories keep Banyira's own money separate from client
 * money (investor capital, borrower obligations, principal and interest).
 */
class LedgerService
{
    /**
     * @param  list<array{0:string,1:string,2:string,3?:string}>  $lines  [account code, debit, credit, memo]
     */
    public function post(string $description, array $lines, ?Model $source = null, ?User $createdBy = null, ?User $approvedBy = null, ?string $date = null, ?int $reverses = null): JournalEntry
    {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('A journal entry needs at least two lines.');
        }

        $debits = Money::add(...array_map(fn ($l) => Money::of($l[1]), $lines));
        $credits = Money::add(...array_map(fn ($l) => Money::of($l[2]), $lines));
        if (Money::cmp($debits, $credits) !== 0) {
            throw new InvalidArgumentException("Journal entry does not balance: debits {$debits}, credits {$credits}.");
        }
        if (Money::isZero($debits)) {
            throw new InvalidArgumentException('Journal entry has no value.');
        }

        $accounts = LedgerAccount::whereIn('code', array_column($lines, 0))->pluck('id', 'code');

        return DB::transaction(function () use ($description, $lines, $source, $createdBy, $approvedBy, $date, $accounts, $reverses) {
            $entry = JournalEntry::create([
                'reference' => Reference::make('JE'),
                'reverses_id' => $reverses,
                'entry_date' => $date ?? now()->toDateString(),
                'description' => $description,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'created_by' => $createdBy?->id,
                'approved_by' => $approvedBy?->id,
                'is_demo' => config('banyira.payments.driver') === 'demo',
            ]);

            foreach ($lines as $l) {
                $id = $accounts[$l[0]] ?? throw new InvalidArgumentException("Unknown ledger account {$l[0]}.");
                $entry->lines()->create(['ledger_account_id' => $id, 'debit' => Money::of($l[1]), 'credit' => Money::of($l[2]), 'memo' => $l[3] ?? null]);
            }

            return $entry->load('lines.account');
        });
    }

    /** Correct a posted entry by posting its mirror image. */
    public function reverse(JournalEntry $entry, User $by, string $reason): JournalEntry
    {
        if ($entry->reverses_id || $entry->reversal()->exists()) {
            throw new InvalidArgumentException('This entry has already been reversed or is itself a reversal.');
        }

        $lines = $entry->lines()->with('account')->get()->map(fn ($l) => [$l->account->code, $l->credit, $l->debit, 'Reversal'])->all();

        return $this->post("Reversal of {$entry->reference}: {$reason}", $lines, $entry->source, $by, $by, null, $entry->id);
    }

    /** Balance per account (debit-positive for assets/expenses, credit-positive otherwise). */
    public function balances(?string $category = null): array
    {
        return LedgerAccount::query()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->leftJoin('journal_lines', 'journal_lines.ledger_account_id', '=', 'ledger_accounts.id')
            ->groupBy('ledger_accounts.id', 'ledger_accounts.code', 'ledger_accounts.name', 'ledger_accounts.type', 'ledger_accounts.category', 'ledger_accounts.is_client_money')
            ->orderBy('ledger_accounts.code')
            ->get(['ledger_accounts.*', DB::raw('COALESCE(SUM(journal_lines.debit),0) as dr'), DB::raw('COALESCE(SUM(journal_lines.credit),0) as cr')])
            ->map(fn ($a) => [
                'code' => $a->code, 'name' => $a->name, 'type' => $a->type, 'category' => $a->category, 'client' => (bool) $a->is_client_money,
                'debit' => Money::of($a->dr), 'credit' => Money::of($a->cr),
                'balance' => in_array($a->type, ['asset', 'expense'], true) ? Money::sub($a->dr, $a->cr) : Money::sub($a->cr, $a->dr),
            ])->all();
    }

    public function categoryTotal(string $category, ?string $from = null, ?string $to = null): string
    {
        $row = DB::table('journal_lines')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('ledger_accounts.category', $category)
            ->when($from, fn ($q) => $q->where('journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('journal_entries.entry_date', '<=', $to))
            ->selectRaw('COALESCE(SUM(debit),0) dr, COALESCE(SUM(credit),0) cr, MAX(ledger_accounts.type) t')
            ->first();

        return in_array($row->t, ['asset', 'expense'], true) ? Money::sub($row->dr, $row->cr) : Money::sub($row->cr, $row->dr);
    }
}
