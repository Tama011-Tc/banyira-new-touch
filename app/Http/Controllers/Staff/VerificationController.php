<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationRecord;
use App\Notifications\Alert;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/** Manual review of customer identity, registration, bank and source-of-funds checks. */
class VerificationController extends Controller
{
    private const REQUIRED = ['business' => ['identity', 'business_registration', 'bank_account'], 'investor' => ['identity', 'source_of_funds', 'bank_account']];

    public function index()
    {
        $users = User::whereIn('type', ['business', 'investor'])
            ->whereHas('verifications', fn ($q) => $q->where('status', 'pending')->whereNotIn('kind', ['email', 'phone']))
            ->with('verifications', 'businessProfile:id,user_id,legal_name,registration_number,industry,region', 'investorProfile:id,user_id,investor_type,source_of_funds,payout_method')
            ->oldest('created_at')->paginate(15);

        return Inertia::render('Staff/Verifications', [
            'users' => $users->through(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'type' => $u->type, 'email' => $u->email, 'phone' => $u->phone, 'created' => $u->created_at,
                'detail' => $u->type === 'business' ? $u->businessProfile?->only(['registration_number', 'industry', 'region']) : $u->investorProfile?->only(['investor_type', 'source_of_funds', 'payout_method']),
                'checks' => $u->verifications->map->only(['id', 'kind', 'status']),
            ]),
        ]);
    }

    public function decide(Request $request, VerificationRecord $record, AuditLogger $audit)
    {
        $data = $request->validate(['status' => ['required', 'in:verified,rejected'], 'notes' => ['nullable', 'string', 'max:250']]);
        $user = $record->user;

        DB::transaction(function () use ($record, $data, $request, $user) {
            $record->update(['status' => $data['status'], 'notes' => $data['notes'] ?? null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            $profile = $user->type === 'business' ? $user->businessProfile : $user->investorProfile;
            $done = $user->verifications()->whereIn('kind', self::REQUIRED[$user->type])->where('status', 'verified')->count() === count(self::REQUIRED[$user->type]);
            $rejected = $user->verifications()->where('status', 'rejected')->exists();
            $profile->forceFill(['verification_status' => $rejected ? 'rejected' : ($done ? 'verified' : 'pending'), 'verified_at' => $done ? now() : null])->save();
            if ($done) {
                $user->notify(new Alert('Account verified', 'Your Banyira account is verified.', '/'.$user->type, sms: true));
            }
        });
        $audit->log('verification', "Marked {$record->kind} as {$data['status']} for {$user->name}", $user, [], $request->user());

        return back()->with('success', 'Verification updated.');
    }
}
