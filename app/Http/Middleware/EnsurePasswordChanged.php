<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Accounts created or reset by an administrator must replace the temporary password before doing anything else. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->must_change_password && ! $request->routeIs('password.change', 'password.change.update', 'logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
