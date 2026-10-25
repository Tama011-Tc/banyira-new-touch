<?php

namespace App\Services;

use App\Models\ComplianceCheck;
use Illuminate\Validation\ValidationException;

/** Guards real-money operation. In this build only simulation is possible; "live" fails closed. */
class Platform
{
    public static function mode(): string
    {
        return Settings::get('operating_mode', 'simulation');
    }

    public static function isSimulation(): bool
    {
        return self::mode() !== 'live';
    }

    public static function checklistComplete(): bool
    {
        return ComplianceCheck::query()->count() > 0 && ComplianceCheck::query()->where('status', '!=', 'approved')->doesntExist();
    }

    /** Called before any commitment, funding or disbursement. */
    public static function assertMayMoveMoney(): void
    {
        if (self::isSimulation()) {
            return; // simulated movements only; ledger rows are flagged simulated
        }
        if (! self::checklistComplete() || ! config('banyira.live_provider_implemented')) {
            throw ValidationException::withMessages(['mode' => 'Real-money operation is not enabled: compliance approvals and a live payment provider are required.']);
        }
    }

    public static function switchToLive(\App\Models\User $actor): void
    {
        if (! self::checklistComplete()) {
            throw ValidationException::withMessages(['mode' => 'Every compliance check must be approved before live operation.']);
        }
        if (! config('banyira.live_provider_implemented')) {
            throw ValidationException::withMessages(['mode' => 'No live payment provider is implemented in this build, so live mode cannot be enabled.']);
        }
        Settings::set('operating_mode', 'live');
        Settings::set('live_approved_by', $actor->id);
        app(AuditLogger::class)->record('platform.live_enabled', null, [], $actor);
    }
}
