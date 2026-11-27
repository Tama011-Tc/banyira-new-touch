<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Notifications\Alert;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StaffAdministrationTest extends TestCase
{
    private function ids(array $slugs): array
    {
        return Permission::whereIn('slug', $slugs)->pluck('id')->all();
    }

    public function test_a_role_can_be_created_and_deleted_once_nobody_holds_it(): void
    {
        $admin = $this->staff('it_admin');

        $this->asStaff($admin)->post('/internal/it/roles', ['name' => 'Web Editor', 'permissions' => $this->ids(['content.manage'])])->assertSessionHasNoErrors();
        $role = Role::where('slug', 'web_editor')->firstOrFail();
        $this->asStaff($admin)->post('/internal/it/roles', ['name' => 'web editor'])->assertSessionHasErrors('name');

        $employee = $this->staff('web_editor');
        $this->asStaff($admin)->delete("/internal/it/roles/{$role->id}")->assertSessionHasErrors('permissions');

        $employee->roles()->detach();
        $this->asStaff($admin)->delete("/internal/it/roles/{$role->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($role);
    }

    public function test_built_in_roles_cannot_be_deleted(): void
    {
        $role = Role::where('slug', 'sales_marketing')->firstOrFail();

        $this->asStaff($this->staff('it_admin'))->delete("/internal/it/roles/{$role->id}")->assertSessionHasErrors('permissions');
        $this->assertModelExists($role);
    }

    public function test_no_new_role_can_mix_platform_administration_with_approval_powers(): void
    {
        $admin = $this->staff('it_admin');

        foreach (['users.manage', 'roles.manage', 'system.manage', 'backups.manage'] as $power) {
            $this->asStaff($admin)->post('/internal/it/roles', ['name' => "Mixed {$power}", 'permissions' => $this->ids([$power, 'finance.approve'])])
                ->assertSessionHasErrors('permissions');
        }
        $this->assertSame(0, Role::where('name', 'like', 'Mixed%')->count());
    }

    public function test_the_employee_page_shows_sign_ins_and_activity_only_with_those_permissions(): void
    {
        $admin = $this->staff('it_admin');
        $target = $this->staff('credit_officer');

        $this->asStaff($admin)->get("/internal/it/users/{$target->id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->where('employee.id', $target->id)->has('signIns')->has('activity')->has('permissions', 3));

        $this->asStaff($admin)->post('/internal/it/roles', ['name' => 'Account Clerk', 'permissions' => $this->ids(['users.manage'])]);
        $this->asStaff($this->staff('account_clerk'))->get("/internal/it/users/{$target->id}")
            ->assertInertia(fn (AssertableInertia $p) => $p->where('signIns', null)->where('activity', null));

        $this->asStaff($admin)->get('/internal/it/users/'.$this->investor()->id)->assertNotFound();
    }

    public function test_an_employee_is_told_when_their_role_changes_and_nobody_changes_their_own(): void
    {
        $admin = $this->staff('it_admin');
        $target = $this->staff('credit_officer');
        $sales = Role::where('slug', 'sales_marketing')->value('id');

        $this->asStaff($admin)->put("/internal/it/users/{$target->id}", ['action' => 'role', 'role_id' => $sales])->assertSessionHasNoErrors();
        $this->assertTrue($target->refresh()->hasPermission('content.manage'));
        Notification::assertSentTo($target, Alert::class);

        $this->asStaff($admin)->put("/internal/it/users/{$admin->id}", ['action' => 'suspend'])->assertSessionHasErrors('action');
    }

    public function test_employee_search_filters_and_ignores_unknown_sorting(): void
    {
        $admin = $this->staff('it_admin', ['name' => 'Zed Admin']);
        $this->staff('credit_officer', ['name' => 'Jane Banda']);
        $this->staff('credit_officer', ['name' => 'Percent 100% Person']);

        $names = fn (string $query) => data_get($this->asStaff($admin)->get('/internal/it/users?'.$query)->assertOk()->inertiaProps(), 'users.data.*.name');

        $this->assertSame(['Jane Banda'], $names('q=JANE'));
        $this->assertSame(['Percent 100% Person'], $names('q=%25'));
        $this->assertSame(['Zed Admin'], $names('role='.Role::where('slug', 'it_admin')->value('id')));
        $this->assertSame(['Jane Banda', 'Percent 100% Person', 'Zed Admin'], $names('sort=password&dir=sideways&status=nonsense&per=100000'));
        $this->assertSame(['Zed Admin', 'Percent 100% Person', 'Jane Banda'], $names('sort=name&dir=desc'));
    }

    public function test_every_employee_lands_on_their_own_overview(): void
    {
        foreach (['credit_officer', 'it_admin', 'sales_marketing', 'finance_officer', 'executive'] as $role) {
            $this->asStaff($this->staff($role))->get('/internal')->assertOk()
                ->assertInertia(fn (AssertableInertia $p) => $p->component('Staff/Home'));
        }
    }

    public function test_an_employee_changes_their_own_details_and_password(): void
    {
        $u = $this->staff('credit_officer');

        $this->asStaff($u)->put('/internal/profile', ['name' => 'New Name', 'phone' => '0999000111', 'email' => 'other@banyira.internal', 'status' => 'suspended'])->assertSessionHasNoErrors();
        $this->assertSame(['New Name', 'active'], [$u->refresh()->name, $u->status]);
        $this->assertStringEndsWith('@banyira.internal', $u->email);
        $this->assertNotSame('other@banyira.internal', $u->email);

        $new = ['password' => 'Another#Pass2027', 'password_confirmation' => 'Another#Pass2027'];
        $this->asStaff($u)->put('/internal/profile/password', ['current_password' => 'wrong', ...$new])->assertSessionHasErrors('current_password');
        $this->asStaff($u)->put('/internal/profile/password', ['current_password' => 'Staff#Pass2026', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->asStaff($u)->put('/internal/profile/password', ['current_password' => 'Staff#Pass2026', ...$new])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Another#Pass2027', $u->refresh()->password));
    }

    public function test_employees_only_mark_their_own_notifications_read(): void
    {
        $mine = $this->staff('credit_officer');
        $other = $this->staff('credit_officer');
        foreach ([$mine, $other] as $u) {
            $u->notifications()->create(['id' => (string) \Str::uuid(), 'type' => Alert::class, 'data' => ['title' => 'T', 'body' => 'B', 'url' => null]]);
        }

        $this->asStaff($mine)->get('/internal/notifications')->assertInertia(fn (AssertableInertia $p) => $p->has('items.data', 1)->where('counts.notifications', 1));
        $this->asStaff($mine)->post('/internal/notifications/read', ['id' => $other->notifications()->value('id')]);
        $this->asStaff($mine)->post('/internal/notifications/read');

        $this->assertSame(0, $mine->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }

    public function test_credit_officers_are_told_about_a_new_application(): void
    {
        $officer = $this->staff('credit_officer');
        $finance = $this->staff('finance_officer');

        $this->submittedApplication($this->business());

        Notification::assertSentTo($officer, Alert::class);
        Notification::assertNotSentTo($finance, Alert::class);
    }
}
