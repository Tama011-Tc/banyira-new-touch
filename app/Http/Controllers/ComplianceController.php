<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ComplianceCheck;
use App\Models\InvestorProfile;
use App\Services\AuditLogger;
use App\Services\Platform;
use App\Services\Settings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ComplianceController extends Controller
{
    public function index()
    {
        return Inertia::render('compliance/Index', [
            'checks' => ComplianceCheck::with('approver:id,name')->orderBy('id')->get()->map(fn ($c) => ['id' => $c->id, 'key' => $c->key, 'label' => $c->label, 'description' => $c->description, 'status' => $c->status, 'approved_by' => $c->approver?->name, 'approved_at' => $c->approved_at?->toDateString(), 'evidence' => $c->evidence]),
            'mode' => Platform::mode(), 'complete' => Platform::checklistComplete(), 'live_possible' => config('banyira.live_provider_implemented'),
            'kyc_queue' => InvestorProfile::with('user:id,name,email')->where('kyc_status', 'submitted')->get()->map(fn ($p) => ['id' => $p->id, 'name' => $p->user->name, 'email' => $p->user->email, 'district' => $p->district, 'phone' => $p->phone, 'national_id_masked' => '••••'.substr((string) $p->national_id, -3)]),
            'business_queue' => Business::where('verification_status', 'pending')->latest('id')->limit(20)->get(['id', 'display_name', 'sector', 'district']),
        ]);
    }

    public function approveCheck(Request $request, ComplianceCheck $check, AuditLogger $audit)
    {
        abort_unless($request->user()->role->can('compliance.workspace'), 403);
        $d = $request->validate(['evidence' => 'required|string|min:10|max:1000']);
        $check->forceFill(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now(), 'evidence' => $d['evidence']])->save();
        $audit->record('compliance.check_approved', $check, ['key' => $check->key]);

        return back()->with('success', 'Recorded.');
    }

    public function goLive(Request $request)
    {
        Platform::switchToLive($request->user());

        return back();
    }

    public function reviewKyc(Request $request, InvestorProfile $profile, AuditLogger $audit)
    {
        $d = $request->validate(['decision' => 'required|in:verified,rejected']);
        abort_unless($profile->kyc_status === 'submitted', 422);
        $profile->forceFill(['kyc_status' => $d['decision'], 'verified_by' => $request->user()->id, 'verified_at' => now()])->save();
        $audit->record('kyc.'.$d['decision'], $profile);

        return back()->with('success', 'Recorded.');
    }

    public function verifyBusiness(Request $request, Business $business, AuditLogger $audit)
    {
        $d = $request->validate(['decision' => 'required|in:verified,rejected']);
        $business->forceFill(['verification_status' => $d['decision']])->save();
        $audit->record('business.'.$d['decision'], $business);

        return back()->with('success', 'Recorded.');
    }
}
