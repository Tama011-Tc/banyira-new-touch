<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\IdentityVerifier;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/** Profile completion and identity verification after registration. */
class OnboardingController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('businessProfile', 'investorProfile');
        if (! $user->phone_verified_at) {
            return redirect('/verify-phone');
        }

        return Inertia::render('Auth/Onboarding', [
            'type' => $user->type,
            'profile' => $user->type === 'business'
                ? $user->businessProfile->only(['legal_name', 'trading_name', 'registration_number', 'region', 'district', 'address', 'years_operating', 'employees', 'monthly_turnover', 'description', 'payout_method'])
                : $user->investorProfile->only(['investor_type', 'source_of_funds', 'payout_method']),
            'regions' => CustomerAuthController::REGIONS,
        ]);
    }

    public function store(Request $request, IdentityVerifier $verifier)
    {
        $user = $request->user();
        $business = $user->type === 'business';
        $data = $request->validate($business ? [
            'registration_number' => ['required', 'string', 'max:60'],
            'trading_name' => ['nullable', 'string', 'max:120'],
            'region' => ['required', 'in:'.implode(',', CustomerAuthController::REGIONS)],
            'district' => ['required', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:200'],
            'years_operating' => ['required', 'integer', 'min:0', 'max:100'],
            'employees' => ['required', 'integer', 'min:0', 'max:100000'],
            'monthly_turnover' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'description' => ['required', 'string', 'max:1000'],
            'national_id' => ['required', 'string', 'max:30'],
            'payout_method' => ['required', 'in:Bank account,Mobile money'],
            'payout_account' => ['required', 'string', 'max:40'],
        ] : [
            'national_id' => ['required', 'string', 'max:30'],
            'source_of_funds' => ['required', 'in:Salary,Business income,Savings,Pension,Other'],
            'payout_method' => ['required', 'in:Bank account,Mobile money'],
            'payout_account' => ['required', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($user, $business, $data, $verifier) {
            if ($business) {
                $user->businessProfile->update(collect($data)->except('national_id')->all());
                $user->businessProfile->forceFill(['verification_status' => 'pending'])->save();
                $verifier->submit($user, 'business_registration', ['reference' => $data['registration_number']]);
            } else {
                $user->investorProfile->update($data);
                $user->investorProfile->forceFill(['verification_status' => 'pending'])->save();
                $verifier->submit($user, 'source_of_funds', []);
            }
            $verifier->submit($user, 'identity', ['reference' => substr($data['national_id'], -4)]);
            $verifier->submit($user, 'bank_account', []);
        });

        return redirect('/'.$user->type)->with('success', 'Thank you. Our team will verify your details within two working days.');
    }
}
