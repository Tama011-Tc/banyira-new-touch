<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\Role;
use App\Models\User;
use App\Services\StaffAccessService;
use App\Support\TableQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/** Employee accounts: the directory, one person's page and changes to their access. */
class EmployeeController extends Controller
{
    public function __construct(private StaffAccessService $access) {}

    public function index(Request $request)
    {
        $roles = Role::orderBy('name')->get(['id', 'name']);
        $table = TableQuery::for(User::where('type', 'employee')->with('roles:id,name,slug', 'employeeProfile'), $request)
            ->search(['name', 'email'])
            ->filter('role', fn ($q, $id) => $q->whereHas('roles', fn ($r) => $r->whereKey($id)), $roles->map(fn ($r) => (string) $r->id)->all())
            ->filter('status', fn ($q, $status) => $q->where('status', $status), ['active', 'suspended'])
            ->sort(['name' => 'name', 'signin' => 'last_login_at'], 'name', 'asc');

        return Inertia::render('Staff/IT/Users', [
            'users' => $table->paginate()->through(fn (User $u) => $this->present($u)),
            'query' => $table->state(),
            'roles' => $roles,
        ]);
    }

    public function show(Request $request, User $user)
    {
        abort_unless($user->isEmployee(), 404);
        $viewer = $request->user();
        $user->load('roles.permissions', 'employeeProfile');

        return Inertia::render('Staff/IT/User', [
            'employee' => [...$this->present($user), 'department' => $user->employeeProfile?->department, 'createdAt' => $user->created_at, 'passwordChangedAt' => $user->password_changed_at],
            'permissions' => $user->roles->flatMap->permissions->unique('id')->sortBy(['group', 'slug'])->values()->map->only(['slug', 'name', 'group']),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'sessions' => DB::table('sessions')->where('user_id', $user->id)->count(),
            // Sign-in addresses and the activity trail follow their own permissions.
            'signIns' => $viewer->hasPermission('security.view')
                ? LoginAttempt::where('user_id', $user->id)->latest('id')->limit(10)->get(['id', 'ip', 'success', 'reason', 'flagged', 'created_at'])
                : null,
            'activity' => $viewer->hasPermission('audit.view')
                ? AuditLog::where('user_id', $user->id)->latest('id')->limit(15)->get(['id', 'area', 'action', 'created_at'])
                : null,
            'isSelf' => $viewer->id === $user->id,
        ]);
    }

    public function store(Request $request)
    {
        // Lowercased before the unique check: Postgres compares text case-sensitively.
        $request->merge(['email' => $request->string('email')->lower()->value()]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email', 'ends_with:@banyira.internal'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'job_title' => ['required', 'string', 'max:80'],
            'department' => ['required', 'string', 'max:60'],
            'password' => ['required', StaffAccessService::passwordRule()],
        ]);
        $this->access->createEmployee($data, $request->user());

        return back()->with('success', 'Employee created. They will set up MFA at first sign-in.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->isEmployee(), 404);
        $data = $request->validate(['action' => ['required', 'in:suspend,activate,unlock,reset_mfa,sign_out,role'], 'role_id' => ['required_if:action,role', 'nullable', 'integer', 'exists:roles,id']]);
        $this->access->changeAccess($user, $data['action'], $data['action'] === 'role' ? (int) $data['role_id'] : null, $request->user());

        return back()->with('success', 'Employee access updated.');
    }

    public function updateDetails(Request $request, User $user)
    {
        abort_unless($user->isEmployee(), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['required', 'string', 'max:80'],
            'department' => ['required', 'string', 'max:60'],
        ]);
        $this->access->updateDetails($user, $data, $request->user());

        return back()->with('success', 'Details saved.');
    }

    private function present(User $u): array
    {
        return [
            ...$u->only(['id', 'name', 'email', 'status', 'last_login_at']),
            'mfa' => (bool) $u->mfa_confirmed_at, 'locked' => $u->isLocked(),
            'role' => $u->roles->first()?->name, 'roleId' => $u->roles->first()?->id,
            'title' => $u->employeeProfile?->job_title, 'staffNo' => $u->employeeProfile?->staff_number,
        ];
    }
}
