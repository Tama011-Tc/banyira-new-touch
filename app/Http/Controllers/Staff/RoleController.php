<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\StaffAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Roles and the permission matrix. */
class RoleController extends Controller
{
    public function __construct(private StaffAccessService $access) {}

    public function index(Request $request)
    {
        $held = $request->user()->roles()->pluck('roles.id')->all();

        return Inertia::render('Staff/IT/Roles', [
            'roles' => Role::with('permissions:id')->withCount('users')->orderBy('name')->get()->map(fn (Role $r) => [
                ...$r->only(['id', 'slug', 'name', 'description', 'users_count']),
                'permissions' => $r->permissions->pluck('id'),
                'builtIn' => StaffAccessService::isBuiltIn($r),
                'own' => in_array($r->id, $held, true),
            ]),
            'permissions' => Permission::orderBy('group')->orderBy('slug')->get(['id', 'slug', 'name', 'group']),
            'separation' => ['admin' => StaffAccessService::ADMIN_POWERS, 'approval' => StaffAccessService::APPROVAL_POWERS],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:200'],
            ...$this->permissionRules(),
        ]);
        $this->access->createRole($data['name'], $data['description'] ?? null, $data['permissions'] ?? [], $request->user());

        return back()->with('success', 'Role created. Give it to employees from their page.');
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate($this->permissionRules());
        $this->access->setRolePermissions($role, $data['permissions'] ?? [], $request->user());

        return back()->with('success', 'Role permissions saved.');
    }

    public function destroy(Request $request, Role $role)
    {
        $this->access->deleteRole($role, $request->user());

        return back()->with('success', 'Role deleted.');
    }

    private function permissionRules(): array
    {
        return ['permissions' => ['array'], 'permissions.*' => ['integer', 'exists:permissions,id']];
    }
}
