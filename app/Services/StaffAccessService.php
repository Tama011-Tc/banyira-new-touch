<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Employee accounts and roles. Two rules always hold: nobody changes their own
 * access, and no role that administers the platform may approve credit or move
 * money.
 */
class StaffAccessService
{
    /** Approval powers over credit and money. */
    public const APPROVAL_POWERS = ['applications.approve', 'listings.publish', 'rules.approve', 'finance.approve', 'finance.record', 'payments.reconcile', 'payroll.view', 'payroll.manage'];

    /** Powers over the platform itself. */
    public const ADMIN_POWERS = ['users.manage', 'roles.manage', 'system.manage', 'backups.manage'];

    private const ADMIN_ROLE = 'it_admin';

    public function __construct(private AuditLogger $audit, private StaffNotifier $notifier) {}

    public static function passwordRule(): Password
    {
        return Password::min(12)->letters()->mixedCase()->numbers()->symbols();
    }

    public static function isBuiltIn(Role $role): bool
    {
        return array_key_exists($role->slug, ReferenceDataSeeder::ROLES);
    }

    /** @param array{name: string, email: string, role_id: int, job_title: string, department: string, password: string} $data */
    public function createEmployee(array $data, User $by): User
    {
        return DB::transaction(function () use ($data, $by) {
            $employee = User::create(['name' => $data['name'], 'email' => $data['email'], 'type' => 'employee', 'status' => 'active', 'password' => $data['password'], 'email_verified_at' => now(), 'password_changed_at' => now()]);
            $employee->roles()->attach($data['role_id']);
            $employee->employeeProfile()->create(['staff_number' => 'BNY-'.str_pad((string) $employee->id, 3, '0', STR_PAD_LEFT), 'department' => $data['department'], 'job_title' => $data['job_title']]);
            $this->audit->log('users', "Created employee account {$employee->email}", $employee, ['role_id' => $data['role_id']], $by);

            return $employee;
        });
    }

    /** @param string $action suspend, activate, unlock, reset_mfa, sign_out or role */
    public function changeAccess(User $employee, string $action, ?int $roleId, User $by): void
    {
        $this->refuseOwnAccount($employee, $by);

        DB::transaction(function () use ($employee, $action, $roleId, $by) {
            match ($action) {
                'suspend' => $employee->forceFill(['status' => 'suspended'])->save(),
                'activate' => $employee->forceFill(['status' => 'active'])->save(),
                'unlock' => $employee->forceFill(['locked_until' => null, 'failed_logins' => 0])->save(),
                'reset_mfa' => $employee->forceFill(['mfa_secret' => null, 'mfa_confirmed_at' => null])->save(),
                'role' => $employee->roles()->sync([$roleId]),
                'sign_out' => null,
            };
            if (in_array($action, ['suspend', 'reset_mfa', 'role', 'sign_out'], true)) {
                $this->endSessions($employee);
            }
            $this->audit->log('users', ucfirst(str_replace('_', ' ', $action))." for {$employee->email}", $employee, array_filter(['action' => $action, 'role_id' => $roleId]), $by);
        });

        $notice = ['activate' => 'Your account was reactivated.', 'reset_mfa' => 'Your authenticator was reset. Set it up again at your next sign-in.', 'role' => 'Your role was changed.'][$action] ?? null;
        if ($notice) {
            $this->notifier->employee($employee, 'Your access changed', $notice);
        }
    }

    public function updateDetails(User $employee, array $data, User $by): void
    {
        DB::transaction(function () use ($employee, $data, $by) {
            $employee->update(['name' => $data['name']]);
            $employee->employeeProfile()->updateOrCreate([], ['job_title' => $data['job_title'], 'department' => $data['department'], 'staff_number' => $employee->employeeProfile?->staff_number ?? 'BNY-'.str_pad((string) $employee->id, 3, '0', STR_PAD_LEFT)]);
            $this->audit->log('users', "Updated details for {$employee->email}", $employee, $data, $by);
        });
    }

    /** @param list<int> $permissionIds */
    public function createRole(string $name, ?string $description, array $permissionIds, User $by): Role
    {
        $slug = Str::slug($name, '_');
        if ($slug === '' || Role::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'Choose a different name. A role with this name already exists.']);
        }
        $slugs = $this->assertSeparation($slug, $permissionIds);

        return DB::transaction(function () use ($slug, $name, $description, $permissionIds, $slugs, $by) {
            $role = Role::create(['slug' => $slug, 'name' => $name, 'description' => $description]);
            $role->permissions()->sync($permissionIds);
            $this->audit->log('roles', "Created role {$name}", $role, ['permissions' => $slugs], $by);

            return $role;
        });
    }

    /** @param list<int> $permissionIds */
    public function setRolePermissions(Role $role, array $permissionIds, User $by): void
    {
        $this->refuseOwnRole($role, $by);
        $slugs = $this->assertSeparation($role->slug, $permissionIds);

        DB::transaction(function () use ($role, $permissionIds, $slugs, $by) {
            $before = $role->permissions()->pluck('slug')->all();
            $role->permissions()->sync($permissionIds);
            $this->audit->log('roles', "Changed permissions for {$role->name}", $role, ['before' => $before, 'after' => $slugs], $by);
        });
    }

    public function deleteRole(Role $role, User $by): void
    {
        $this->refuseOwnRole($role, $by);
        if (self::isBuiltIn($role)) {
            throw ValidationException::withMessages(['permissions' => 'Built-in roles cannot be deleted.']);
        }

        DB::transaction(function () use ($role, $by) {
            // Locking the role stops someone being given it while it is removed.
            $role = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();
            if ($role->users()->exists()) {
                throw ValidationException::withMessages(['permissions' => 'Move the people in this role to another role first.']);
            }
            $this->audit->log('roles', "Deleted role {$role->name}", $role, ['permissions' => $role->permissions()->pluck('slug')->all()], $by);
            $role->delete();
        });
    }

    public function endSessions(User $user, ?string $exceptSessionId = null): void
    {
        DB::table('sessions')->where('user_id', $user->id)->when($exceptSessionId, fn ($q, $id) => $q->where('id', '!=', $id))->delete();
    }

    /** @return list<string> the permission slugs, once they are known to be allowed together */
    private function assertSeparation(string $roleSlug, array $permissionIds): array
    {
        $slugs = Permission::whereIn('id', $permissionIds)->pluck('slug');
        $administers = $roleSlug === self::ADMIN_ROLE || $slugs->intersect(self::ADMIN_POWERS)->isNotEmpty();
        if ($administers && $slugs->intersect(self::APPROVAL_POWERS)->isNotEmpty()) {
            throw ValidationException::withMessages(['permissions' => 'A role that administers the platform cannot also hold financial or credit approval powers.']);
        }

        return $slugs->all();
    }

    private function refuseOwnAccount(User $employee, User $by): void
    {
        if ($employee->id === $by->id) {
            throw ValidationException::withMessages(['action' => 'You cannot change your own account access.']);
        }
    }

    private function refuseOwnRole(Role $role, User $by): void
    {
        if ($by->roles()->whereKey($role->id)->exists()) {
            throw ValidationException::withMessages(['permissions' => 'You cannot change the permissions of a role you hold.']);
        }
    }
}
