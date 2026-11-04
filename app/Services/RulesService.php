<?php

namespace App\Services;

use App\Models\RuleSet;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Financing rules: service-fee tiers, investor interest ceiling and risk-band
 * limits. Rules are versioned; a proposal must be approved by someone other
 * than its proposer and becomes active on its effective date.
 */
class RulesService
{
    public function __construct(private AuditLogger $audit) {}

    public function active(): RuleSet
    {
        return RuleSet::with(['feeTiers', 'riskBands'])->where('status', 'active')->firstOrFail();
    }

    /** Fee tier boundary: a tier applies when amount < upper_bound. */
    public function feeRate(string $amount, ?RuleSet $rules = null): string
    {
        $rules ??= $this->active();
        foreach ($rules->feeTiers as $tier) {
            if ($tier->upper_bound === null || Money::cmp($amount, $tier->upper_bound) < 0) {
                return $tier->rate;
            }
        }

        return $rules->feeTiers->last()->rate;
    }

    public function serviceFee(string $principal, ?RuleSet $rules = null): array
    {
        $rate = $this->feeRate($principal, $rules);

        return ['rate' => $rate, 'fee' => Money::pct($principal, $rate)];
    }

    public function classForScore(float $score, ?RuleSet $rules = null): ?string
    {
        $rules ??= $this->active();
        foreach ($rules->riskBands as $band) {             // ordered by min_score desc
            if ($score >= $band->min_score) {
                return $band->risk_class;
            }
        }

        return null;
    }

    /** @return list<string> human-readable breaches of the class limits */
    public function bandBreaches(string $class, string $amount, int $months, ?RuleSet $rules = null): array
    {
        $band = ($rules ?? $this->active())->band($class);
        $out = [];
        if ($band?->max_amount !== null && Money::cmp($amount, $band->max_amount) > 0) {
            $out[] = "Class {$class} financing is limited to ".Money::format($band->max_amount).'.';
        }
        if ($band?->max_months !== null && $months > $band->max_months) {
            $out[] = "Class {$class} financing is limited to {$band->max_months} months.";
        }

        return $out;
    }

    public function propose(array $data, User $by): RuleSet
    {
        if (RuleSet::where('status', 'pending_approval')->exists()) {
            throw ValidationException::withMessages(['rules' => 'A proposal is already awaiting approval.']);
        }

        return DB::transaction(function () use ($data, $by) {
            $set = RuleSet::create([
                'version' => 'R-'.now()->format('Y').'.'.(RuleSet::count() + 1),
                'effective_from' => $data['effective_from'],
                'status' => 'pending_approval',
                'interest_ceiling' => $data['interest_ceiling'],
                'fee_basis' => 'principal',
                'fee_collection' => 'deducted_at_disbursement',
                'notes' => $data['notes'] ?? null,
                'proposed_by' => $by->id,
            ]);
            foreach (array_values($data['fee_tiers']) as $i => $t) {
                $set->feeTiers()->create(['upper_bound' => $t['upper_bound'] ?: null, 'rate' => $t['rate'], 'sort_order' => $i]);
            }
            foreach ($data['bands'] as $b) {
                $set->riskBands()->create(['risk_class' => $b['risk_class'], 'label' => $b['label'], 'min_score' => $b['min_score'], 'max_amount' => $b['max_amount'] ?: null, 'max_months' => $b['max_months'] ?: null]);
            }
            $this->audit->log('rules', 'Proposed financing rules '.$set->version, $set, [], $by);

            return $set;
        });
    }

    public function decide(RuleSet $set, bool $approve, User $by, ?string $note = null): RuleSet
    {
        if ($set->status !== 'pending_approval') {
            throw ValidationException::withMessages(['rules' => 'This proposal has already been decided.']);
        }
        if ($set->proposed_by === $by->id) {
            throw ValidationException::withMessages(['rules' => 'Rules cannot be approved by the person who proposed them.']);
        }

        $set->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $by->id, 'decided_at' => now(), 'notes' => trim(($set->notes ?? '').' '.($note ?? '')) ?: null]);
        $this->audit->log('rules', ($approve ? 'Approved' : 'Rejected').' financing rules '.$set->version, $set, ['note' => $note], $by);
        $this->activateDue();

        return $set->refresh();
    }

    /** Activate approved rule sets whose effective date has arrived. Run daily. */
    public function activateDue(): void
    {
        DB::transaction(function () {
            $due = RuleSet::where('status', 'approved')->whereDate('effective_from', '<=', now())->orderBy('effective_from')->lockForUpdate()->get();
            foreach ($due as $set) {
                RuleSet::where('status', 'active')->update(['status' => 'superseded']);
                $set->update(['status' => 'active']);
            }
        });
    }
}
