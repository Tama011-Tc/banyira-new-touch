<?php

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Records every sign-in attempt, locks accounts after repeated failures and
 * flags suspicious patterns (many failures from one IP, a new IP for staff).
 */
class LoginGuard
{
    public function __construct(private AuditLogger $audit, private StaffNotifier $staff) {}

    public function record(Request $request, string $guard, ?User $user, bool $success, ?string $reason = null): void
    {
        $ip = (string) $request->ip();
        $recentFailsFromIp = LoginAttempt::where('ip', $ip)->where('success', false)->where('created_at', '>', now()->subHour())->count();
        $newIp = $success && $user && $guard === 'staff' && $user->last_login_ip && $user->last_login_ip !== $ip;

        LoginAttempt::create([
            'guard' => $guard, 'email' => strtolower((string) $request->input('email', $user?->email)), 'user_id' => $user?->id,
            'ip' => $ip, 'user_agent' => substr((string) $request->userAgent(), 0, 255), 'success' => $success, 'reason' => $reason,
            'flagged' => $recentFailsFromIp >= 5 || $newIp,
        ]);

        if (! $user) {
            return;
        }
        if ($success) {
            $user->forceFill(['failed_logins' => 0, 'locked_until' => null, 'last_login_at' => now(), 'last_login_ip' => $ip])->save();

            return;
        }

        $max = config('banyira.security.'.($guard === 'staff' ? 'staff' : 'customer').'_max_attempts');
        $user->increment('failed_logins');
        if ($user->failed_logins >= $max) {
            $user->forceFill(['locked_until' => now()->addMinutes(config('banyira.security.lockout_minutes')), 'failed_logins' => 0])->save();
            $this->audit->log('security', "Account locked after {$max} failed sign-ins", $user, ['guard' => $guard, 'ip' => $ip]);
            $this->staff->holders('security.view', 'Account locked', "{$user->email} was locked after {$max} failed sign-ins.", '/internal/it/security');
        }
    }
}
