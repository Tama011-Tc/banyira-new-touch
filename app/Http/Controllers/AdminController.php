<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\InvestorProfile;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * IT administration: accounts, access, system configuration and monitoring. Nothing here can change a credit
 * decision, a loan, a payment, the ledger or the audit trail; those have no write path for this role at all.
 */
class AdminController extends Controller
{
    public function users(Request $request)
    {
        $q = User::query()->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))->when($request->role, fn ($q, $r) => $q->where('role', $r))->orderBy('name');

        return Inertia::render('admin/Users', [
            'users' => $q->paginate(20)->withQueryString()->through(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role->value, 'role_label' => $u->role->label(), 'status' => $u->status]),
            'roles' => collect(Role::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label(), 'staff' => $r->isStaff()]),
            'filters' => $request->only('search', 'role'),
        ]);
    }

    /**
     * Create a staff account. If no password is typed, a strong one is generated and shown ONCE on the next page.
     * Either way the person must replace it at first sign-in.
     */
    public function storeStaff(Request $request, AuditLogger $audit)
    {
        $d = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|unique:users,email', 'password' => ['nullable', Password::min(12)->mixedCase()->numbers()], 'role' => ['required', Rule::in(collect(Role::cases())->filter->isStaff()->map->value->all())]]);
        $generated = empty($d['password']);
        $password = $generated ? Str::password(16, symbols: false).'a1A' : $d['password'];
        $u = new User(['name' => $d['name'], 'email' => strtolower($d['email']), 'password' => $password]);
        $u->forceFill(['role' => Role::from($d['role']), 'status' => 'active', 'email_verified_at' => now(), 'must_change_password' => true])->save();
        $audit->record('admin.staff_created', $u, ['role' => $d['role'], 'password_generated' => $generated]);

        $back = back()->with('success', 'Staff account created. They must change the password at first sign-in.');

        return $generated ? $back->with('temp_credential', ['email' => $u->email, 'password' => $password]) : $back;
    }

    /** Issue a new temporary password to a locked-out person. The old one stops working and they must choose their own. */
    public function resetPassword(Request $request, User $user, AuditLogger $audit)
    {
        abort_if($user->id === $request->user()->id, 422, 'Use Change password for your own account.');
        $password = Str::password(16, symbols: false).'a1A';
        $user->forceFill(['password' => $password, 'must_change_password' => true])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $audit->record('admin.password_reset', $user);

        return back()->with('success', 'Password reset. Share the temporary password securely.')->with('temp_credential', ['email' => $user->email, 'password' => $password]);
    }

    public function changeRole(Request $request, User $user, AuditLogger $audit)
    {
        $d = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);
        $new = Role::from($d['role']);
        abort_if($user->id === $request->user()->id, 422, 'You cannot change your own role.');
        if (! $user->role->isStaff() || ! $new->isStaff()) {
            throw ValidationException::withMessages(['role' => 'Customer accounts and staff accounts are separate: a role cannot move between them.']);
        }
        $old = $user->role->value;
        $user->forceFill(['role' => $new])->save();
        $audit->record('admin.role_changed', $user, ['from' => $old, 'to' => $new->value]);

        return back()->with('success', 'Role updated and logged.');
    }

    public function suspend(Request $request, User $user, AuditLogger $audit)
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot suspend yourself.');
        $to = $request->boolean('suspend') ? 'suspended' : 'active';
        $user->forceFill(['status' => $to])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();   // revoke access immediately
        $audit->record($to === 'suspended' ? 'admin.suspended' : 'admin.reinstated', $user, ['reason' => (string) $request->input('reason')]);

        return back()->with('success', $to === 'suspended' ? 'Account suspended and signed out.' : 'Account reinstated.');
    }

    public function settings()
    {
        $keys = ['moderation.rules', 'messages.max_per_minute', 'loan.default_days', 'loan.max_rate_bp', 'loan.max_term_months', 'loan.max_grace_instalments', 'investor.min_commitment', 'credit.executive_threshold', 'credit.separate_approver'];

        return Inertia::render('admin/Settings', [
            'values' => collect($keys)->mapWithKeys(fn ($k) => [$k => Settings::get($k)]),
            'rules' => array_keys(\App\Support\MessageModerator::rules()),
            'note' => 'Fee rates are not editable here: they need finance approval (Finance → fee change). Credit decisions, payments and the ledger cannot be edited by any role.',
        ]);
    }

    public function updateSettings(Request $request, AuditLogger $audit)
    {
        $d = $request->validate(['moderation_rules' => 'array', 'moderation_rules.*' => Rule::in(array_keys(\App\Support\MessageModerator::rules())), 'default_days' => 'required|integer|min:30|max:365', 'max_rate' => 'required|numeric|min:1|max:100', 'max_term' => 'required|integer|min:1|max:120']);
        Settings::set('moderation.rules', array_values($d['moderation_rules'] ?? []));
        Settings::set('loan.default_days', (int) $d['default_days']);
        Settings::set('loan.max_rate_bp', (int) round($d['max_rate'] * 100));
        Settings::set('loan.max_term_months', (int) $d['max_term']);
        $audit->record('admin.settings_updated', null, ['keys' => array_keys($d)]);

        return back()->with('success', 'Settings saved and logged.');
    }

    public function audit(Request $request, AuditLogger $logger)
    {
        $q = AuditLog::query()->with('actor:id,name')->when($request->action, fn ($q, $a) => $q->where('action', 'like', $a.'%'))->orderByDesc('id');

        return Inertia::render('admin/Audit', [
            'logs' => $q->paginate(30)->withQueryString()->through(fn ($l) => ['id' => $l->id, 'at' => $l->created_at->toDateTimeString(), 'actor' => $l->actor?->name ?? 'System', 'role' => $l->actor_role, 'action' => $l->action, 'subject' => $l->subject_type ? "{$l->subject_type} #{$l->subject_id}" : null, 'meta' => $l->meta, 'ip' => $l->ip, 'hash' => substr($l->hash, 0, 10)]),
            'chain_ok' => $logger->verifyChain(), 'filters' => $request->only('action'),
        ]);
    }

    public function health()
    {
        return Inertia::render('admin/Health', [
            'checks' => [
                ['name' => 'Database', 'ok' => (bool) DB::selectOne('select 1 ok')->ok, 'detail' => DB::getDriverName()],
                ['name' => 'Failed jobs (queue)', 'ok' => DB::table('failed_jobs')->count() === 0, 'detail' => DB::table('failed_jobs')->count().' failed'],
                ['name' => 'Rejected webhooks', 'ok' => \App\Models\WebhookEvent::where('outcome', 'rejected')->count() === 0, 'detail' => \App\Models\WebhookEvent::where('outcome', 'rejected')->count().' with invalid signatures'],
                ['name' => 'Audit chain', 'ok' => app(AuditLogger::class)->verifyChain(), 'detail' => AuditLog::count().' entries'],
                ['name' => 'Operating mode', 'ok' => true, 'detail' => \App\Services\Platform::mode()],
            ],
            'failed_jobs' => DB::table('failed_jobs')->latest('id')->limit(10)->get(['id', 'queue', 'failed_at']),
            'errors' => \App\Models\WebhookEvent::where('outcome', 'rejected')->latest('created_at')->limit(10)->get(['event_id', 'outcome', 'created_at']),
        ]);
    }
}
