<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RuleSet;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** IT administration: health, employees, roles, security, audit, jobs and integrations. */
class AdminController extends Controller
{
    /** Approval powers the IT role may never hold. */
    private const SEPARATED = ['applications.approve', 'listings.publish', 'rules.approve', 'finance.approve', 'finance.record', 'payments.reconcile', 'payroll.view', 'payroll.manage'];

    public function __construct(private AuditLogger $audit) {}

    public function health()
    {
        $t = microtime(true);
        DB::select('select 1');
        $dbMs = round((microtime(true) - $t) * 1000, 1);
        $disk = config('banyira.documents_disk');

        return Inertia::render('Staff/IT/Health', [
            'checks' => [
                ['name' => 'Database', 'status' => 'healthy', 'note' => "{$dbMs} ms"],
                ['name' => 'Queue', 'status' => DB::table('jobs')->count() > 100 ? 'degraded' : 'healthy', 'note' => DB::table('jobs')->count().' waiting · '.DB::table('failed_jobs')->count().' failed'],
                ['name' => 'Document storage', 'status' => $this->writable($disk) ? 'healthy' : 'failing', 'note' => 'Disk: '.$disk],
                ['name' => 'SMS', 'status' => SmsMessage::where('status', 'failed')->where('created_at', '>', now()->subDay())->exists() ? 'degraded' : 'healthy', 'note' => 'Driver: '.config('banyira.sms.driver')],
                ['name' => 'Payments', 'status' => 'healthy', 'note' => 'Driver: '.config('banyira.payments.driver').(config('banyira.payments.driver') === 'demo' ? ' (no real money)' : '')],
                ['name' => 'Financing rules', 'status' => RuleSet::where('status', 'active')->count() === 1 ? 'healthy' : 'failing', 'note' => RuleSet::where('status', 'active')->value('version') ?? 'none active'],
            ],
            'stats' => [
                'users' => User::count(),
                'sessions' => DB::table('sessions')->where('last_activity', '>', now()->subMinutes(30)->timestamp)->count(),
                'failedLogins' => LoginAttempt::where('success', false)->where('created_at', '>', now()->subDay())->count(),
                'flagged' => LoginAttempt::where('flagged', true)->where('created_at', '>', now()->subWeek())->count(),
            ],
            'env' => ['php' => PHP_VERSION, 'laravel' => app()->version(), 'env' => app()->environment(), 'debug' => config('app.debug')],
        ]);
    }

    public function users()
    {
        return Inertia::render('Staff/IT/Users', [
            'users' => User::where('type', 'employee')->with('roles:id,name,slug', 'employeeProfile')->orderBy('name')->get()
                ->map(fn ($u) => [...$u->only(['id', 'name', 'email', 'status', 'last_login_at']), 'mfa' => (bool) $u->mfa_confirmed_at, 'locked' => $u->isLocked(),
                    'role' => $u->roles->first()?->name, 'roleId' => $u->roles->first()?->id, 'title' => $u->employeeProfile?->job_title, 'staffNo' => $u->employeeProfile?->staff_number]),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email', 'ends_with:@banyira.internal'],
            'role_id' => ['required', 'exists:roles,id'],
            'job_title' => ['required', 'string', 'max:80'],
            'department' => ['required', 'string', 'max:60'],
            'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);
        DB::transaction(function () use ($data, $request) {
            $u = User::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'type' => 'employee', 'status' => 'active', 'password' => $data['password'], 'email_verified_at' => now(), 'password_changed_at' => now()]);
            $u->roles()->attach($data['role_id']);
            $u->employeeProfile()->create(['staff_number' => 'BNY-'.str_pad((string) $u->id, 3, '0', STR_PAD_LEFT), 'department' => $data['department'], 'job_title' => $data['job_title']]);
            $this->audit->log('users', "Created employee account {$u->email}", $u, ['role_id' => $data['role_id']], $request->user());
        });

        return back()->with('success', 'Employee created. They will set up MFA at first sign-in.');
    }

    public function updateUser(Request $request, User $user)
    {
        abort_unless($user->isEmployee(), 404);
        $data = $request->validate(['action' => ['required', 'in:suspend,activate,unlock,reset_mfa,role'], 'role_id' => ['required_if:action,role', 'nullable', 'exists:roles,id']]);
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages(['action' => 'You cannot change your own account access.']);
        }
        match ($data['action']) {
            'suspend' => $user->forceFill(['status' => 'suspended'])->save(),
            'activate' => $user->forceFill(['status' => 'active'])->save(),
            'unlock' => $user->forceFill(['locked_until' => null, 'failed_logins' => 0])->save(),
            'reset_mfa' => $user->forceFill(['mfa_secret' => null, 'mfa_confirmed_at' => null])->save(),
            'role' => $user->roles()->sync([$data['role_id']]),
        };
        if (in_array($data['action'], ['suspend', 'reset_mfa', 'role'], true)) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        $this->audit->log('users', ucfirst(str_replace('_', ' ', $data['action']))." for {$user->email}", $user, $data, $request->user());

        return back()->with('success', 'Employee access updated.');
    }

    public function roles()
    {
        return Inertia::render('Staff/IT/Roles', [
            'roles' => Role::with('permissions:id')->withCount('users')->orderBy('name')->get()->map(fn ($r) => [...$r->only(['id', 'slug', 'name', 'description', 'users_count']), 'permissions' => $r->permissions->pluck('id')]),
            'permissions' => Permission::orderBy('group')->orderBy('slug')->get(['id', 'slug', 'name', 'group']),
            'separated' => self::SEPARATED,
        ]);
    }

    public function updateRole(Request $request, Role $role)
    {
        $data = $request->validate(['permissions' => ['array'], 'permissions.*' => ['exists:permissions,id']]);
        if ($request->user()->roles->contains($role)) {
            throw ValidationException::withMessages(['permissions' => 'You cannot change the permissions of a role you hold.']);
        }
        $slugs = Permission::whereIn('id', $data['permissions'] ?? [])->pluck('slug');
        if ($role->slug === 'it_admin' && $slugs->intersect(self::SEPARATED)->isNotEmpty()) {
            throw ValidationException::withMessages(['permissions' => 'IT administrators cannot hold financial or credit approval powers.']);
        }
        $before = $role->permissions()->pluck('slug')->all();
        $role->permissions()->sync($data['permissions'] ?? []);
        $this->audit->log('roles', "Changed permissions for {$role->name}", $role, ['before' => $before, 'after' => $slugs->all()], $request->user());

        return back()->with('success', 'Role permissions saved.');
    }

    public function security()
    {
        return Inertia::render('Staff/IT/Security', [
            'attempts' => LoginAttempt::latest('id')->paginate(25)->through(fn ($a) => $a->only(['id', 'guard', 'email', 'ip', 'success', 'reason', 'flagged', 'created_at'])),
            'locked' => User::whereNotNull('locked_until')->where('locked_until', '>', now())->get(['id', 'name', 'email', 'type', 'locked_until']),
        ]);
    }

    public function jobs()
    {
        return Inertia::render('Staff/IT/Jobs', [
            'queued' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->latest('id')->limit(20)->get(['id', 'queue', 'exception', 'failed_at'])->map(fn ($j) => ['id' => $j->id, 'queue' => $j->queue, 'error' => strtok($j->exception, "\n"), 'at' => $j->failed_at]),
            'sms' => SmsMessage::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
            'schedule' => [
                ['task' => 'Activate approved financing rules', 'when' => 'Daily 00:05'],
                ['task' => 'Flag overdue instalments', 'when' => 'Daily 01:00'],
                ['task' => 'Expire stale investor offers', 'when' => 'Hourly'],
                ['task' => 'Repayment reminders (SMS)', 'when' => 'Daily 07:00'],
            ],
            'integrations' => [
                ['name' => 'SMS', 'driver' => config('banyira.sms.driver'), 'configured' => config('banyira.sms.driver') === 'log' || filled(config('banyira.sms.africastalking.api_key')), 'env' => 'SMS_DRIVER, AFRICASTALKING_USERNAME, AFRICASTALKING_API_KEY'],
                ['name' => 'Payments', 'driver' => config('banyira.payments.driver'), 'configured' => config('banyira.payments.driver') === 'demo', 'env' => 'PAYMENTS_DRIVER, AIRTEL_MONEY_*, TNM_MPAMBA_API_KEY, PAYMENTS_WEBHOOK_SECRET'],
                ['name' => 'Email', 'driver' => config('mail.default'), 'configured' => config('mail.default') !== 'log', 'env' => 'MAIL_MAILER, MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD'],
                ['name' => 'Identity verification', 'driver' => config('banyira.identity.driver'), 'configured' => true, 'env' => 'IDENTITY_DRIVER'],
                ['name' => 'Document storage', 'driver' => config('banyira.documents_disk'), 'configured' => true, 'env' => 'DOCUMENTS_DISK, AWS_*'],
            ],
        ]);
    }

    public function retryJobs(Request $request)
    {
        Artisan::call('queue:retry', ['id' => ['all']]);
        $this->audit->log('system', 'Retried failed jobs', null, [], $request->user());

        return back()->with('success', 'Failed jobs queued for retry.');
    }

    public function audit(Request $request)
    {
        $area = $request->query('area');

        return Inertia::render('Staff/Audit', [
            'logs' => AuditLog::with('user:id,name')->when($area, fn ($q) => $q->where('area', $area))->latest('id')->paginate(30)->withQueryString()
                ->through(fn ($l) => [...$l->only(['id', 'area', 'action', 'ip', 'created_at', 'meta']), 'user' => $l->user?->name ?? 'System']),
            'areas' => AuditLog::distinct()->orderBy('area')->pluck('area'),
            'area' => $area,
        ]);
    }

    private function writable(string $disk): bool
    {
        try {
            Storage::disk($disk)->put('.health', (string) time());

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
