<?php

namespace App\Services;

use App\Support\Money;

/**
 * Fees are disclosed up front, snapshotted on each offer version, and never recalculated after acceptance.
 * With no approved fee schedule in force every fee is zero.
 */
class FeeService
{
    /** The fees that would apply to a loan of $principal today (tier by amount). */
    public static function snapshot(?int $principal = null): array
    {
        $schedule = app(FeeScheduleService::class)->active();
        $tier = $schedule ? self::tierFor($schedule->tiers, $principal ?? 0) : null;

        return [
            'approved' => $schedule !== null, 'schedule' => $schedule?->name,
            'origination_bp' => (int) ($tier['origination_bp'] ?? 0), 'origination_fixed' => (int) ($tier['origination_fixed'] ?? 0), 'origination_payer' => 'borrower', 'origination_basis' => 'principal',
            'investor_service_bp' => (int) ($tier['investor_bp'] ?? 0), 'investor_service_fixed' => (int) ($tier['investor_fixed'] ?? 0), 'investor_service_payer' => 'investor', 'investor_service_basis' => 'principal',
            'processing_bp' => (int) ($schedule?->processing_bp ?? 0), 'processing_payer' => 'platform', 'processing_basis' => 'each payment',
            'tax_bp' => (int) ($schedule?->tax_bp ?? 0),
        ];
    }

    public static function tierFor(array $tiers, int $principal): ?array
    {
        foreach ($tiers as $t) {
            if ($principal >= $t['min'] && ($t['max'] === null || $principal < $t['max'])) {
                return $t;
            }
        }

        return null;
    }

    /** @return array{origination:int, origination_tax:int, investor_service:int, investor_service_tax:int, borrower_total:int, investor_total:int, net_to_business:int, investor_pays:int} */
    public static function onPrincipal(array $fees, int $principal): array
    {
        $orig = Money::bp($principal, $fees['origination_bp']) + (int) ($fees['origination_fixed'] ?? 0);
        $origTax = Money::bp($orig, $fees['tax_bp']);
        $inv = Money::bp($principal, $fees['investor_service_bp']) + (int) ($fees['investor_service_fixed'] ?? 0);
        $invTax = Money::bp($inv, $fees['tax_bp']);

        return [
            'origination' => $orig, 'origination_tax' => $origTax, 'investor_service' => $inv, 'investor_service_tax' => $invTax,
            'borrower_total' => $orig + $origTax, 'investor_total' => $inv + $invTax,
            'net_to_business' => $principal - $orig - $origTax, 'investor_pays' => $principal + $inv + $invTax,
        ];
    }

    public static function processing(array $fees, int $paymentAmount): int
    {
        return Money::bp($paymentAmount, $fees['processing_bp']);
    }
}
