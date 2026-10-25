<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Runtime configuration stored in the database. Every change goes through AuditLogger at the call site. */
class Settings
{
    public const DEFAULTS = [
        'operating_mode' => 'simulation',
        'fees.origination_bp' => 200,          // borrower, % of principal, deducted from the disbursement
        'fees.investor_service_bp' => 100,     // investor, % of principal, added to the funding payment
        'fees.processing_bp' => 150,           // charged by the payment provider on each payment; borne by the platform
        'fees.tax_bp' => 0,                    // tax on platform fees; set per advice (e.g. VAT) before live use
        'credit.separate_approver' => 1,       // approver must differ from the analyst
        'credit.executive_threshold' => 1500000000, // MK 15,000,000: larger amounts need an executive
        'credit.weights' => ['dscr' => 40, 'history' => 15, 'documents' => 15, 'debt' => 15, 'sector' => 15],
        'credit.sector_scores' => ['Agriculture' => 60, 'Retail & trade' => 75, 'Manufacturing' => 65, 'Transport & logistics' => 60, 'Services' => 70, 'Construction' => 55, 'Food & hospitality' => 62, 'Technology' => 68, 'Education' => 72, 'Health' => 74],
        'loan.default_days' => 90,
        'loan.max_grace_instalments' => 3,
        'loan.max_rate_bp' => 4500,
        'loan.max_term_months' => 36,
        'investor.min_commitment' => 5000000,   // MK 50,000
        'moderation.rules' => null,             // null = all rules on
        'messages.max_per_minute' => 6,
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = DB::table('settings')->where('key', $key)->value('value');

        return $row !== null ? json_decode($row, true) : (self::DEFAULTS[$key] ?? $default);
    }

    public static function set(string $key, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()]);
    }
}
