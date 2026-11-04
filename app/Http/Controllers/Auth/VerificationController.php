<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\PhoneVerificationCode;
use App\Models\SmsMessage;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Phone verification by SMS code, and email verification by signed link. */
class VerificationController extends Controller
{
    public function showPhone(Request $request)
    {
        $user = $request->user();
        if ($user->phone_verified_at) {
            return redirect('/onboarding');
        }
        if (! PhoneVerificationCode::where('user_id', $user->id)->where('expires_at', '>', now())->exists()) {
            $this->sendCode($user);
        }

        return Inertia::render('Auth/VerifyPhone', [
            'phone' => substr($user->phone, 0, 4).' ••• '.substr($user->phone, -3),
            // Only in local development, so the flow can be tested without an SMS provider.
            'devCode' => app()->environment('local') && config('banyira.sms.driver') === 'log' ? session('dev_sms_code') : null,
        ]);
    }

    public function verifyPhone(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        $code = PhoneVerificationCode::where('user_id', $user->id)->where('expires_at', '>', now())->latest('id')->first();

        if (! $code || $code->attempts >= 5) {
            throw ValidationException::withMessages(['code' => 'This code has expired. Request a new one.']);
        }
        if (! Hash::check($request->input('code'), $code->code_hash)) {
            $code->increment('attempts');
            throw ValidationException::withMessages(['code' => 'That code is not correct. Check the SMS and try again.']);
        }

        $user->forceFill(['phone_verified_at' => now(), 'status' => 'active'])->save();
        $user->verifications()->updateOrCreate(['kind' => 'phone'], ['status' => 'verified', 'reviewed_at' => now(), 'provider' => 'sms']);
        PhoneVerificationCode::where('user_id', $user->id)->delete();
        session()->forget('dev_sms_code');

        return redirect('/onboarding')->with('success', 'Phone number verified.');
    }

    public function resend(Request $request)
    {
        $this->sendCode($request->user());

        return back()->with('success', 'A new code is on its way.');
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();
        $request->user()->verifications()->updateOrCreate(['kind' => 'email'], ['status' => 'verified', 'reviewed_at' => now(), 'provider' => 'email']);

        return redirect('/'.$request->user()->type)->with('success', 'Email address confirmed.');
    }

    public function resendEmail(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'We sent a new confirmation link to your email.');
    }

    private function sendCode($user): void
    {
        $code = (string) random_int(100000, 999999);
        PhoneVerificationCode::where('user_id', $user->id)->delete();
        PhoneVerificationCode::create(['user_id' => $user->id, 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(10)]);
        $sms = SmsMessage::create(['user_id' => $user->id, 'to' => $user->phone, 'body' => "Your Banyira verification code is {$code}. It expires in 10 minutes. Never share it.", 'driver' => config('banyira.sms.driver')]);
        SendSms::dispatch($sms->id);
        if (app()->environment('local')) {
            session(['dev_sms_code' => $code]);
        }
    }
}
