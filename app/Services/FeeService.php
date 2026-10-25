<?php

namespace App\Services;

use App\Support\Money;

/** Fees are disclosed up front, snapshotted on each offer version, and never recalculated after acceptance. */
class FeeService
{
    public static function snapshot(): array
    {
        return [
            'origination_bp' => (int) Settings::get('fees.origination_bp'), 'origination_payer' => 'borrower', 'origination_basis' => 'principal',
            'investor_service_bp' => (int) Settings::get('fees.investor_service_bp'), 'investor_service_payer' => 'investor', 'investor_service_basis' => 'principal',
            'processing_bp' => (int) Settings::get('fees.processing_bp'), 'processing_payer' => 'platform', 'processing_basis' => 'each payment',
            'tax_bp' => (int) Settings::get('fees.tax_bp'),
        ];
    }

    /** @return array{origination:int, origination_tax:int, investor_service:int, investor_service_tax:int, borrower_total:int, investor_total:int, net_to_business:int, investor_pays:int} */
    public static function onPrincipal(array $fees, int $principal): array
    {
        $orig = Money::bp($principal, $fees['origination_bp']);
        $origTax = Money::bp($orig, $fees['tax_bp']);
        $inv = Money::bp($principal, $fees['investor_service_bp']);
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
