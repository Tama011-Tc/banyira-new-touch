<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\InvestorProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /** Public sign-up creates investors or business owners only. Staff accounts are created by an IT administrator. */
    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'account_type' => ['required', Rule::in(['investor', 'business_owner'])],
        ]);

        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->forceFill(['role' => Role::from($data['account_type']), 'status' => 'active'])->save();
        if ($user->role === Role::Investor) {
            InvestorProfile::create(['user_id' => $user->id]);
        }

        event(new Registered($user));
        Auth::login($user);
        $audit->record('auth.registered', $user, ['account_type' => $data['account_type']], $user);

        return to_route('dashboard');
    }
}
