<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Business;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Sales helps businesses apply. A rep sees only businesses they registered, can never record a credit decision, and
 * cannot mark an application as approved. Every assisted registration records the rep and the owner's consent.
 */
class SalesController extends Controller
{
    public function index(Request $request)
    {
        $ids = Business::where('registered_by_staff_id', $request->user()->id)->pluck('id');
        $apps = Application::whereIn('business_id', $ids)->with('business')->latest('id')->get();

        return Inertia::render('sales/Index', [
            'businesses' => Business::whereIn('id', $ids)->withCount('applications')->latest('id')->get()->map(fn ($b) => ['id' => $b->id, 'display_name' => $b->display_name, 'sector' => $b->sector, 'district' => $b->district, 'claimed' => (bool) $b->owner_id, 'applications' => $b->applications_count]),
            'applications' => $apps->map(fn ($a) => ['id' => $a->id, 'business' => $a->business->display_name, 'status' => $a->status, 'amount' => $a->amount]),
        ]);
    }

    public function create()
    {
        return Inertia::render('sales/Create', ['sectors' => config('banyira.sectors'), 'districts' => config('banyira.districts')]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $d = $request->validate(BusinessController::rules() + ['consent' => 'accepted', 'owner_name' => 'required|string|max:120', 'owner_email' => 'required|email|max:255|unique:users,email']);
        // The owner gets their own account (no password known to staff) and completes the application themselves, or lets the rep assist.
        $owner = new User(['name' => $d['owner_name'], 'email' => strtolower($d['owner_email']), 'password' => Str::random(40)]);
        $owner->forceFill(['role' => \App\Enums\Role::BusinessOwner, 'status' => 'active'])->save();
        $b = Business::create(collect($d)->except(['consent', 'owner_name', 'owner_email'])->all() + ['owner_id' => $owner->id, 'registered_by_staff_id' => $request->user()->id, 'owner_consent_at' => now()]);
        $audit->record('business.registered_by_staff', $b, ['owner_consent' => true]);

        return to_route('applications.create', ['business' => $b->id])->with('success', 'Business registered with the owner’s consent. The owner can set their password via “Forgot password”.');
    }

    /** Referral = hand a complete submitted application to the credit team. No status power beyond that. */
    public function refer(Request $request, Application $application)
    {
        abort_unless(Business::where('id', $application->business_id)->where('registered_by_staff_id', $request->user()->id)->exists(), 404);
        abort_unless($application->status === 'submitted', 422);
        $application->forceFill(['referred_by' => $request->user()->id])->save();
        app(AuditLogger::class)->record('application.referred', $application);

        return back()->with('success', 'Referred to the credit team.');
    }
}
