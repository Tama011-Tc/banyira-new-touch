<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Runtime configuration stored in the database. Every change goes through AuditLogger at the call site. */
class Settings
{
    public const DEFAULTS = [
        'operating_mode' => 'simulation',
        // Fees come only from an approved fee schedule (see FeeScheduleService). None is in force by default.
        'campaign.min_funded_pct' => 100,      // a business is paid only once this share of the target is funded
        'offers.ttl_days' => 7,                // an unanswered proposal lapses after this many days
        'investor.max_total_commitment' => null, // per-investor cap across all loans (minor units); null = no cap
        'sms.confirm_acceptance' => false,     // require a one-time SMS code to accept an offer
        'credit.separate_approver' => 1,       // approver must differ from the analyst
        'credit.executive_threshold' => 1500000000, // MK 15,000,000: larger amounts need an executive
        // Risk bands: highest first. Thresholds and wording are credit POLICY, changed only through a two-person approval.
        // Defaults follow the band names in the business-plan brief (A+, A, B, C); confirm against the plan.
        'credit.bands' => [
            ['code' => 'A+', 'min' => 85, 'name' => 'Very low estimated risk', 'definition' => 'Strong repayment capacity, long record, complete verified documents.'],
            ['code' => 'A', 'min' => 70, 'name' => 'Low estimated risk', 'definition' => 'Good repayment capacity and a consistent record with minor gaps.'],
            ['code' => 'B', 'min' => 55, 'name' => 'Moderate estimated risk', 'definition' => 'Adequate repayment capacity; some weaknesses in record, documents or existing debt.'],
            ['code' => 'C', 'min' => 0, 'name' => 'Elevated estimated risk', 'definition' => 'Thin repayment capacity or material weaknesses. Listed only if approved with conditions.'],
        ],
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
