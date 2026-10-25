<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountsTest extends TestCase
{
    public function test_the_first_admin_is_created_from_the_console_and_must_change_password(): void
    {
        $this->artisan('banyira:create-admin', ['email' => 'first@bank.test', '--name' => 'First Admin'])->expectsOutputToContain('Temporary password')->assertSuccessful();
        $u = User::where('email', 'first@bank.test')->first();
        $this->assertSame(Role::ItAdmin, $u->role);
        $this->assertTrue($u->must_change_password);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.bootstrap_created']);
        $this->artisan('banyira:create-admin', ['email' => 'first@bank.test'])->assertFailed();                      // duplicate
        $this->artisan('banyira:create-admin', ['email' => 'x@bank.test', '--password' => 'short'])->assertFailed();    // weak
    }

    public function test_admin_creates_staff_with_a_generated_password_shown_once(): void
    {
        $admin = $this->user(Role::ItAdmin);
        $res = $this->actingAs($admin)->post('/admin/users', ['name' => 'Ana Analyst', 'email' => 'ana@bank.test', 'role' => 'credit_analyst']);
        $res->assertSessionHasNoErrors()->assertSessionHas('temp_credential');
        $temp = session('temp_credential');
        $u = User::where('email', 'ana@bank.test')->first();
        $this->assertSame(Role::CreditAnalyst, $u->role);
        $this->assertTrue($u->must_change_password);
        $this->assertTrue(Hash::check($temp['password'], $u->password));
        $this->assertGreaterThanOrEqual(16, strlen($temp['password']));
        $audit = \App\Models\AuditLog::where('action', 'admin.staff_created')->first();
        $this->assertStringNotContainsString($temp['password'], json_encode($audit->meta), 'passwords are never logged');
        $this->actingAs($admin)->get('/admin/users')->assertOk();                          // the credential was flashed, not stored
        $this->assertNull(session('temp_credential'));
    }

    public function test_only_admins_can_create_accounts_and_never_customers_or_other_ways(): void
    {
        foreach ([Role::Executive, Role::Finance, Role::Compliance, Role::Support, Role::Investor, Role::BusinessOwner] as $r) {
            $this->actingAs($this->user($r))->post('/admin/users', ['name' => 'X', 'email' => "x{$r->value}@bank.test", 'role' => 'finance'])->assertForbidden();
        }
        $admin = $this->actingAs($this->user(Role::ItAdmin));
        $admin->post('/admin/users', ['name' => 'X', 'email' => 'inv@bank.test', 'role' => 'investor'])->assertSessionHasErrors('role');
        $admin->post('/admin/users', ['name' => 'X', 'email' => 'bo@bank.test', 'role' => 'business_owner'])->assertSessionHasErrors('role');
        $this->assertSame(0, User::whereIn('email', ['inv@bank.test', 'bo@bank.test'])->count());
    }

    public function test_a_new_staff_member_is_locked_to_the_password_page_until_they_change_it(): void
    {
        $admin = $this->user(Role::ItAdmin);
        $this->actingAs($admin)->post('/admin/users', ['name' => 'Fin Officer', 'email' => 'fin@bank.test', 'role' => 'finance']);
        $temp = session('temp_credential')['password'];
        auth()->logout();

        $this->post('/login', ['email' => 'fin@bank.test', 'password' => $temp]);
        $this->get('/finance')->assertRedirect('/password/change');
        $this->get('/dashboard')->assertRedirect('/password/change');
        $this->get('/password/change')->assertOk();
        $this->put('/password/change', ['current_password' => 'wrong', 'password' => 'A-Brand-New-Pass-1', 'password_confirmation' => 'A-Brand-New-Pass-1'])->assertSessionHasErrors('current_password');
        $this->put('/password/change', ['current_password' => $temp, 'password' => 'weak', 'password_confirmation' => 'weak'])->assertSessionHasErrors('password');
        $this->put('/password/change', ['current_password' => $temp, 'password' => $temp, 'password_confirmation' => $temp])->assertSessionHasErrors('password');
        $this->put('/password/change', ['current_password' => $temp, 'password' => 'A-Brand-New-Pass-1', 'password_confirmation' => 'A-Brand-New-Pass-1'])->assertSessionHasNoErrors();
        $this->get('/finance')->assertOk();
        $this->assertFalse(User::where('email', 'fin@bank.test')->first()->must_change_password);
    }

    public function test_admin_can_reset_a_password_which_signs_the_person_out_and_forces_a_change(): void
    {
        $admin = $this->user(Role::ItAdmin);
        $staff = $this->user(Role::Support, ['password' => Hash::make('Old-Password-123')]);
        \Illuminate\Support\Facades\DB::table('sessions')->insert(['id' => 's1', 'user_id' => $staff->id, 'payload' => 'x', 'last_activity' => time()]);
        $this->actingAs($admin)->post("/admin/users/{$staff->id}/reset-password")->assertSessionHas('temp_credential');
        $staff->refresh();
        $this->assertFalse(Hash::check('Old-Password-123', $staff->password));
        $this->assertTrue($staff->must_change_password);
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $staff->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.password_reset', 'actor_id' => $admin->id]);
        $this->actingAs($admin)->post("/admin/users/{$admin->id}/reset-password")->assertStatus(422);        // not your own
        $this->actingAs($this->user(Role::Finance))->post("/admin/users/{$staff->id}/reset-password")->assertForbidden();
    }

    public function test_public_registration_remains_closed_to_staff_roles(): void
    {
        $this->post('/register', ['name' => 'Sneaky', 'email' => 'sneaky@x.test', 'password' => 'Secret-pass-123', 'password_confirmation' => 'Secret-pass-123', 'account_type' => 'it_admin'])->assertSessionHasErrors('account_type');
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@x.test']);
    }
}
