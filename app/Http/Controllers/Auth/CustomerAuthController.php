<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Services\LoginGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Business and investor sign-in and registration. Employees cannot sign in here. */
class CustomerAuthController extends Controller
{
    public const INDUSTRIES = ['Agriculture', 'Manufacturing', 'Construction', 'Mining', 'Tourism', 'Transportation', 'Retail', 'Services', 'Other'];

    public const REGIONS = ['Northern Region', 'Central Region', 'Southern Region'];

    public function choose(string $mode): Response
    {
        return Inertia::render('Auth/Choose', ['mode' => $mode]);
    }

    public function showLogin(string $type): Response
    {
        return Inertia::render('Auth/Login', ['type' => $type]);
    }

    public function login(Request $request, string $type, LoginGuard $guard)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', strtolower($data['email']))->first();

        if ($user?->isLocked()) {
            throw ValidationException::withMessages(['email' => 'Too many failed attempts. Try again after '.$user->locked_until->format('H:i').' or reset your password.']);
        }
        if (! $user || $user->type !== $type || ! Hash::check($data['password'], $user->password)) {
            $guard->record($request, 'web', $user && $user->type === $type ? $user : null, false, 'invalid_credentials');
            throw ValidationException::withMessages(['email' => 'The email or password is not correct for a '.$type.' account.']);
        }
        if (in_array($user->status, ['suspended', 'closed'], true)) {
            throw ValidationException::withMessages(['email' => 'This account is not active. Please contact support.']);
        }

        auth('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $guard->record($request, 'web', $user, true);

        return redirect()->intended('/'.$type);
    }

    public function showRegister(string $type): Response
    {
        return Inertia::render('Auth/Register', ['type' => $type, 'industries' => self::INDUSTRIES]);
    }

    public function register(RegisterRequest $request, string $type)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $type) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'type' => $type,
                'status' => 'pending', 'password' => $data['password'], 'password_changed_at' => now(),
            ]);
            if ($type === 'business') {
                $p = new BusinessProfile(['legal_name' => $data['name'], 'industry' => $data['industry'], 'business_type' => $data['business_type']]);
                $p->user_id = $user->id;
                $p->save();
            } else {
                $user->investorProfile()->create(['investor_type' => $data['investor_type'], 'risk_acknowledged_at' => now()]);
            }

            return $user;
        });

        $user->sendEmailVerificationNotification();
        auth('web')->login($user);
        $request->session()->regenerate();

        return redirect('/verify-phone');
    }

    public function logout(Request $request)
    {
        auth('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
