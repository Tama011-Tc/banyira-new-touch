<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\StaffAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** An employee's own details, password and notifications. */
class ProfileController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function show(Request $request)
    {
        $user = $request->user()->load('employeeProfile', 'roles:id,name');

        return Inertia::render('Staff/Profile', [
            'account' => [
                ...$user->only(['name', 'email', 'phone', 'last_login_at', 'password_changed_at']),
                'role' => $user->roles->first()?->name,
                'title' => $user->employeeProfile?->job_title,
                'department' => $user->employeeProfile?->department,
                'staffNo' => $user->employeeProfile?->staff_number,
            ],
        ]);
    }

    public function update(Request $request)
    {
        // Work email, role and job details are managed by an administrator.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        $user = $request->user();
        $before = $user->only(array_keys($data));
        $user->update($data);
        $this->audit->log('users', 'Updated own profile', $user, ['before' => $before], $user);

        return back()->with('success', 'Profile saved.');
    }

    public function password(Request $request, StaffAccessService $access)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:staff'],
            'password' => ['required', 'confirmed', 'different:current_password', StaffAccessService::passwordRule()],
        ]);
        $user = $request->user();
        $user->forceFill(['password' => $data['password'], 'password_changed_at' => now()])->save();
        $access->endSessions($user, exceptSessionId: $request->session()->getId());
        $this->audit->log('security', 'Changed own password', $user, [], $user);

        return back()->with('success', 'Password changed. Your other sessions have been signed out.');
    }

    public function notifications(Request $request)
    {
        return Inertia::render('Staff/Notifications', ['items' => $request->user()->notificationFeed()]);
    }

    public function readNotifications(Request $request)
    {
        $data = $request->validate(['id' => ['nullable', 'uuid']]);
        $request->user()->markNotificationsRead($data['id'] ?? null);

        return back();
    }
}
