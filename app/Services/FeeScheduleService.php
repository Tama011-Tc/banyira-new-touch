<?php

namespace App\Services;

use App\Models\FeeSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Fees exist only through an APPROVED schedule. No schedule in force means no fee is charged: the platform never
 * charges something that has not been disclosed and approved.
 */
class FeeScheduleService
{
    public function active(?CarbonImmutable $on = null): ?FeeSchedule
    {
        $day = ($on ?? CarbonImmutable::now())->toDateString();

        return FeeSchedule::query()->where('status', 'approved')->whereDate('effective_from', '<=', $day)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $day))->orderByDesc('effective_from')->orderByDesc('id')->first();
    }

    /** Throws unless the tiers cover every amount from 0 upward once, in order, with no gaps or overlaps. */
    public function validateTiers(array $tiers): array
    {
        $tiers = array_values($tiers);
        if (! $tiers) {
            throw ValidationException::withMessages(['tiers' => 'Add at least one tier.']);
        }
        usort($tiers, fn ($a, $b) => $a['min'] <=> $b['min']);
        foreach ($tiers as $i => $t) {
            $next = $tiers[$i + 1] ?? null;
            if ($i === 0 && $t['min'] !== 0) {
                throw ValidationException::withMessages(['tiers' => 'The first tier must start at 0.']);
            }
            if ($next === null && $t['max'] !== null) {
                throw ValidationException::withMessages(['tiers' => 'The last tier must have no upper limit.']);
            }
            if ($next !== null && ($t['max'] === null || $t['max'] !== $next['min'])) {
                throw ValidationException::withMessages(['tiers' => 'Tiers must follow each other with no gaps or overlaps.']);
            }
            if ($t['max'] !== null && $t['max'] <= $t['min']) {
                throw ValidationException::withMessages(['tiers' => 'A tier’s upper limit must be above its lower limit.']);
            }
        }

        return $tiers;
    }

    /**
     * The business plan's fee promise, enforced: one simple service charge of 4% to 15% paid by the business, and no other fees
     * (no investor fee, no fixed charges, no monitoring/review/security/early-repayment fees). Limits come from settings.
     */
    public function validatePolicy(array $tiers): void
    {
        $min = (int) Settings::get('fees.policy_min_bp');
        $max = (int) Settings::get('fees.policy_max_bp');
        foreach ($tiers as $t) {
            if ($t['origination_bp'] < $min || $t['origination_bp'] > $max) {
                throw ValidationException::withMessages(['tiers' => 'The business service charge must be between '.($min / 100).'% and '.($max / 100).'% in every tier.']);
            }
            if (! Settings::get('fees.allow_investor_fee') && ($t['investor_bp'] > 0 || $t['investor_fixed'] > 0)) {
                throw ValidationException::withMessages(['tiers' => 'Investors are not charged a fee under the current policy.']);
            }
            if (! Settings::get('fees.allow_fixed') && ($t['origination_fixed'] > 0 || $t['investor_fixed'] > 0)) {
                throw ValidationException::withMessages(['tiers' => 'Fixed charges are not allowed: the service charge is a simple percentage.']);
            }
        }
    }

    /** Called when a second person approves the request. Retires the schedule it replaces from the day before. */
    public function publish(array $payload, User $approver, ?int $requestedBy = null): FeeSchedule
    {
        $tiers = $this->validateTiers($payload['tiers']);
        $this->validatePolicy($tiers);
        $from = CarbonImmutable::parse($payload['effective_from']);
        FeeSchedule::query()->where('status', 'approved')->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from->toDateString()))
            ->whereDate('effective_from', '<', $from->toDateString())->update(['effective_to' => $from->subDay()->toDateString()]);

        return FeeSchedule::create([
            'name' => $payload['name'], 'effective_from' => $from->toDateString(), 'tax_bp' => $payload['tax_bp'], 'processing_bp' => $payload['processing_bp'], 'tiers' => $tiers,
            'requested_by' => $requestedBy, 'approved_by' => $approver->id, 'approved_at' => now(),
        ]);
    }
}
