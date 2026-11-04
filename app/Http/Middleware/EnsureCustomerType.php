<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Keeps business and investor areas strictly separate. */
class EnsureCustomerType
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user('web');
        if (! $user || $user->type !== $type) {
            abort(403, 'This area is for '.$type.' accounts.');
        }
        if (in_array($user->status, ['suspended', 'closed'], true)) {
            auth('web')->logout();

            return redirect('/login')->with('error', 'This account is not active. Please contact support.');
        }
        if (! $user->phone_verified_at) {
            return redirect('/verify-phone');
        }

        return $next($request);
    }
}
