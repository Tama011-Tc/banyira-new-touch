<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LoginGuard;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Employee sign-in: password, then an authenticator-app code. Unlinked from the
 * public site, but protected by real controls: rate limits, lockout, MFA,
 * idle timeout and a full sign-in log.
 */
class StaffAuthController extends Controller
{
    public function __construct(private LoginGuard $guard, private AuditLogger $audit) {}

    public function create(Request $request)
    {
        if ($request->user('staff') && $request->session()->get('staff_mfa_passed')) {
            return redirect('/internal');
        }

        return Inertia::render('Staff/Auth/Login');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', strtolower($data['email']))->where('type', 'employee')->first();

        if ($user?->isLocked()) {
            throw ValidationException::withMessages(['email' => 'This account is locked after repeated failed attempts. The security team has been notified.']);
        }
        if (! $user || $user->status !== 'active' || ! Hash::check($data['password'], $user->password)) {
            $this->guard->record($request, 'staff', $user, false, 'invalid_credentials');
            throw ValidationException::withMessages(['email' => 'Those credentials are not valid.']);
        }

        auth('staff')->login($user);
        $request->session()->regenerate();
        $request->session()->forget('staff_mfa_passed');

        return redirect($user->mfa_confirmed_at ? '/internal-access/verify' : '/internal-access/setup');
    }

    public function showSetup(Request $request)
    {
        $user = $this->pending($request);
        if ($user->mfa_confirmed_at) {
            return redirect('/internal-access/verify');
        }
        if (! $user->mfa_secret) {
            $user->forceFill(['mfa_secret' => Totp::generateSecret()])->save();
        }

        return Inertia::render('Staff/Auth/MfaSetup', [
            'secret' => trim(chunk_split($user->mfa_secret, 4, ' ')),
            'uri' => Totp::uri($user->mfa_secret, $user->email),
        ]);
    }

    public function confirmSetup(Request $request)
    {
        $user = $this->pending($request);
        $request->validate(['code' => ['required', 'digits:6']]);
        if (! Totp::verify($user->mfa_secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'That code did not match. Check the time on your phone and try again.']);
        }
        $user->forceFill(['mfa_confirmed_at' => now()])->save();
        $this->audit->log('security', 'Enrolled in multi-factor authentication', $user, [], $user);

        return $this->complete($request, $user);
    }

    public function showVerify(Request $request)
    {
        $this->pending($request);

        return Inertia::render('Staff/Auth/MfaVerify');
    }

    public function verify(Request $request)
    {
        $user = $this->pending($request);
        $request->validate(['code' => ['required', 'digits:6']]);
        if (! Totp::verify((string) $user->mfa_secret, $request->input('code'))) {
            $this->guard->record($request, 'staff', $user, false, 'invalid_mfa');
            if ($user->refresh()->isLocked()) {
                auth('staff')->logout();

                return redirect('/internal-access')->with('error', 'Too many failed attempts. The account is locked.');
            }
            throw ValidationException::withMessages(['code' => 'That code is not valid.']);
        }

        return $this->complete($request, $user);
    }

    public function destroy(Request $request)
    {
        $this->audit->log('security', 'Signed out', null, [], $request->user('staff'));
        auth('staff')->logout();
        $request->session()->forget(['staff_mfa_passed', 'staff_last_seen']);
        $request->session()->regenerateToken();

        return redirect('/internal-access');
    }

    private function complete(Request $request, User $user)
    {
        $request->session()->regenerate();
        $request->session()->put(['staff_mfa_passed' => true, 'staff_last_seen' => time()]);
        $this->guard->record($request, 'staff', $user, true);
        $this->audit->log('security', 'Signed in to the employee portal', $user, [], $user);

        return redirect('/internal');
    }

    private function pending(Request $request): User
    {
        $user = $request->user('staff');
        abort_unless($user && $user->isEmployee(), 403);

        return $user;
    }
}
