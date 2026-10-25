<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ForcedPasswordController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('auth/ChangePassword', ['forced' => (bool) $request->user()->must_change_password]);
    }

    public function update(Request $request, AuditLogger $audit)
    {
        $d = $request->validate(['current_password' => 'required|current_password', 'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers(), 'different:current_password']]);
        $user = $request->user();
        $user->forceFill(['password' => $d['password'], 'must_change_password' => false])->save();
        $audit->record('auth.password_changed', $user, [], $user);

        return to_route('dashboard')->with('success', 'Password updated.');
    }
}
