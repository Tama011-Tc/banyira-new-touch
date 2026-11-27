<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        // Same response whether or not the account exists, so emails cannot be enumerated.
        $user = User::where('email', strtolower($request->input('email')))->where('type', '!=', 'employee')->first();
        if ($user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('success', 'If an account exists for that address, a reset link is on its way. It expires in 60 minutes.');
    }

    public function edit(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::defaults()]]);

        // Emails are stored lowercased, and Postgres compares them case-sensitively.
        $credentials = ['email' => $request->string('email')->lower()->value()] + $request->only('password', 'password_confirmation', 'token');

        $status = Password::reset($credentials, function (User $user, string $password) {
            abort_if($user->isEmployee(), 403);
            $user->forceFill(['password' => $password, 'password_changed_at' => now(), 'failed_logins' => 0, 'locked_until' => null])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect('/login')->with('success', 'Password updated. You can now log in.')
            : back()->withErrors(['email' => __($status)]);
    }
}
