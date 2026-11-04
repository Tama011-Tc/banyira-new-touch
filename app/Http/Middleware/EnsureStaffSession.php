<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Employee portal gate: a staff-guard session, a completed MFA challenge and
 * no more than the configured idle time since the last request.
 */
class EnsureStaffSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('staff');
        $idle = config('banyira.security.staff_idle_minutes') * 60;
        $last = (int) $request->session()->get('staff_last_seen', 0);

        if (! $user || ! $user->isEmployee() || $user->status !== 'active' || ! $request->session()->get('staff_mfa_passed')) {
            return $this->out($request, null);
        }
        if ($last && time() - $last > $idle) {
            return $this->out($request, 'Your session expired after '.config('banyira.security.staff_idle_minutes').' minutes of inactivity.');
        }
        $request->session()->put('staff_last_seen', time());

        return $next($request);
    }

    private function out(Request $request, ?string $msg): Response
    {
        auth('staff')->logout();
        $request->session()->forget(['staff_mfa_passed', 'staff_last_seen']);

        return redirect('/internal-access')->with('error', $msg);
    }
}
