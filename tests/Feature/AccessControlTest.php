<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/marketplace', '/credit', '/finance', '/admin/users', '/applications', '/agreements'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_public_registration_only_creates_investors_or_business_owners(): void
    {
        $base = ['name' => 'Test', 'password' => 'Secret-pass-123', 'password_confirmation' => 'Secret-pass-123'];
        $this->post('/register', $base + ['email' => 'inv@example.test', 'account_type' => 'investor'])->assertRedirect('/dashboard');
        $this->assertSame(Role::Investor, User::where('email', 'inv@example.test')->first()->role);
        $this->assertNotNull(User::where('email', 'inv@example.test')->first()->investorProfile);
        $this->post('/logout');

        foreach (['executive', 'it_admin', 'credit_approver', 'finance'] as $bad) {
            $this->post('/register', $base + ['email' => "$bad@example.test", 'account_type' => $bad])->assertSessionHasErrors('account_type');
        }
        $this->assertSame(0, User::where('email', 'like', '%@example.test')->whereNotIn('email', ['inv@example.test'])->count());
    }

    public function test_role_and_status_cannot_be_mass_assigned(): void
    {
        $u = new User(['name' => 'x', 'email' => 'x@x.test', 'password' => 'p', 'role' => 'executive', 'status' => 'active']);
        $this->assertNotSame('executive', $u->role?->value);
    }

    public function test_each_workspace_is_closed_to_the_wrong_roles(): void
    {
        $matrix = [
            '/marketplace' => [Role::Investor, [Role::BusinessOwner, Role::Sales, Role::CreditAnalyst, Role::Finance, Role::ItAdmin, Role::Support]],
            '/credit' => [Role::CreditAnalyst, [Role::Investor, Role::BusinessOwner, Role::Sales, Role::ItAdmin, Role::Finance, Role::Support]],
            '/sales' => [Role::Sales, [Role::Investor, Role::BusinessOwner, Role::CreditAnalyst, Role::Executive, Role::ItAdmin]],
            '/finance' => [Role::Finance, [Role::Investor, Role::BusinessOwner, Role::Sales, Role::CreditAnalyst, Role::CreditApprover, Role::ItAdmin, Role::Support]],
            '/executive' => [Role::Executive, [Role::Investor, Role::BusinessOwner, Role::Sales, Role::CreditApprover, Role::Finance, Role::ItAdmin]],
            '/compliance' => [Role::Compliance, [Role::Investor, Role::BusinessOwner, Role::Sales, Role::CreditAnalyst, Role::Finance, Role::ItAdmin]],
            '/admin/users' => [Role::ItAdmin, [Role::Investor, Role::BusinessOwner, Role::Sales, Role::CreditApprover, Role::Executive, Role::Finance, Role::Compliance, Role::Support]],
            '/moderation' => [Role::Support, [Role::Investor, Role::BusinessOwner, Role::Sales, Role::CreditAnalyst, Role::Finance, Role::ItAdmin]],
            '/listings' => [Role::CreditApprover, [Role::Investor, Role::BusinessOwner, Role::Sales, Role::CreditAnalyst, Role::Finance, Role::ItAdmin]],
            '/audit' => [Role::ItAdmin, [Role::Investor, Role::BusinessOwner, Role::Sales, Role::CreditAnalyst, Role::CreditApprover, Role::Support]],
        ];
        foreach ($matrix as $url => [$allowed, $denied]) {
            $this->actingAs($this->user($allowed))->get($url)->assertOk();
            foreach ($denied as $role) {
                $this->actingAs($this->user($role))->get($url)->assertForbidden();
            }
        }
    }

    public function test_it_admin_has_no_route_to_money_or_credit_data(): void
    {
        $admin = $this->actingAs($this->user(Role::ItAdmin));
        foreach (['/credit', '/finance', '/finance/ledger', '/listings', '/executive', '/marketplace'] as $url) {
            $admin->get($url)->assertForbidden();
        }
        // and no way to touch a decision, a payment or a disbursement
        $loan = $this->activeLoan();
        $admin->post("/agreements/{$loan->id}/default", ['reason' => 'because'])->assertForbidden();
        $admin->post('/finance/fees', [])->assertForbidden();
        $this->assertFalse(Role::ItAdmin->can('credit.approve'));
        $this->assertFalse(Role::ItAdmin->can('finance.approve'));
    }

    public function test_sales_cannot_decide_assess_or_approve(): void
    {
        $sales = $this->actingAs($this->user(Role::Sales));
        $a = $this->application($this->business());
        $sales->post("/credit/{$a->id}/decide", ['decision' => 'approved', 'reason' => 'I promise funding'])->assertForbidden();
        $sales->post("/credit/{$a->id}/assess", [])->assertForbidden();
        $sales->get("/credit/{$a->id}")->assertForbidden();
        $this->assertFalse(Role::Sales->can('credit.approve'));
    }

    public function test_investors_cannot_open_confidential_documents(): void
    {
        $listing = $this->listing();
        $doc = ApplicationDocument::where('application_id', $listing->application_id)->first();
        $this->actingAs($this->user(Role::Investor))->get("/documents/{$doc->id}")->assertNotFound();
        $this->actingAs($this->user(Role::BusinessOwner))->get("/documents/{$doc->id}")->assertNotFound();   // another business
        $this->actingAs($this->user(Role::Finance))->get("/documents/{$doc->id}")->assertNotFound();
    }

    public function test_document_access_is_audited_for_permitted_staff(): void
    {
        $listing = $this->listing();
        $doc = ApplicationDocument::where('application_id', $listing->application_id)->first();
        \Illuminate\Support\Facades\Storage::disk('local')->put($doc->path, 'x');
        $analyst = $this->user(Role::CreditAnalyst);
        $this->actingAs($analyst)->get("/documents/{$doc->id}")->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.viewed', 'actor_id' => $analyst->id]);
        $this->actingAs($listing->application->business->owner)->get("/documents/{$doc->id}")->assertOk();
    }

    public function test_applicants_cannot_see_each_others_applications_by_guessing_ids(): void
    {
        $mine = $this->application($this->business());
        $other = $this->application($this->business());
        $me = $mine->business->owner;
        $this->actingAs($me)->get("/applications/{$mine->id}")->assertOk();
        $this->actingAs($me)->get("/applications/{$other->id}")->assertNotFound();
        $this->actingAs($me)->put("/applications/{$other->id}", [])->assertNotFound();
        $this->actingAs($me)->post("/applications/{$other->id}/submit")->assertNotFound();
        $this->actingAs($me)->post("/applications/{$other->id}/withdraw")->assertNotFound();
    }

    public function test_business_owner_cannot_approve_their_own_application(): void
    {
        $a = $this->application($this->business());
        $owner = $a->business->owner;
        $this->actingAs($owner)->post("/credit/{$a->id}/decide", ['decision' => 'approved', 'reason' => 'Approve myself please'])->assertForbidden();
        $this->actingAs($owner)->get("/credit/{$a->id}")->assertForbidden();
        $this->assertSame('draft', $a->fresh()->status);
    }

    public function test_investor_cannot_view_other_investors_agreements(): void
    {
        $a = $this->agreement();
        $this->actingAs($this->user(Role::Investor))->get("/agreements/{$a->id}")->assertNotFound();
        $this->actingAs($a->investor)->get("/agreements/{$a->id}")->assertOk();
        $this->actingAs($a->business->owner)->get("/agreements/{$a->id}")->assertOk();
        $this->actingAs($this->user(Role::BusinessOwner))->get("/agreements/{$a->id}")->assertNotFound();
    }

    public function test_suspended_users_are_signed_out_and_cannot_log_in_pages(): void
    {
        $u = $this->user(Role::Investor);
        $this->actingAs($u)->get('/marketplace')->assertOk();
        $u->forceFill(['status' => 'suspended'])->save();
        $this->get('/marketplace')->assertRedirect('/login');
    }

    public function test_admin_can_manage_accounts_but_changes_are_logged_and_bounded(): void
    {
        $admin = $this->user(Role::ItAdmin);
        $staff = $this->user(Role::Support);
        $cust = $this->user(Role::Investor);
        $this->actingAs($admin)->put("/admin/users/{$staff->id}/role", ['role' => 'finance'])->assertSessionHasNoErrors();
        $this->assertSame(Role::Finance, $staff->fresh()->role);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.role_changed', 'actor_id' => $admin->id]);
        $this->actingAs($admin)->put("/admin/users/{$cust->id}/role", ['role' => 'executive'])->assertSessionHasErrors('role');   // customer -> staff blocked
        $this->actingAs($admin)->put("/admin/users/{$admin->id}/role", ['role' => 'executive'])->assertStatus(422);              // not your own
        $this->actingAs($admin)->post("/admin/users/{$cust->id}/suspend", ['suspend' => true, 'reason' => 'fraud review'])->assertSessionHasNoErrors();
        $this->assertSame('suspended', $cust->fresh()->status);
    }

    public function test_staff_creation_requires_a_strong_password_and_staff_role(): void
    {
        $admin = $this->actingAs($this->user(Role::ItAdmin));
        $admin->post('/admin/users', ['name' => 'A', 'email' => 'a@b.test', 'password' => 'short', 'role' => 'finance'])->assertSessionHasErrors('password');
        $admin->post('/admin/users', ['name' => 'A', 'email' => 'a@b.test', 'password' => 'Long-Enough-Pass-1', 'role' => 'investor'])->assertSessionHasErrors('role');
        $admin->post('/admin/users', ['name' => 'A', 'email' => 'a@b.test', 'password' => 'Long-Enough-Pass-1', 'role' => 'finance'])->assertSessionHasNoErrors();
        $this->assertSame(Role::Finance, User::where('email', 'a@b.test')->first()->role);
    }

    public function test_audit_log_and_ledger_cannot_be_edited_or_deleted_even_with_raw_sql(): void
    {
        $this->activeLoan();
        $this->assertGreaterThan(0, DB::table('audit_logs')->count());
        foreach (['audit_logs', 'ledger_entries', 'message_moderation_events'] as $table) {
            if (DB::table($table)->count() === 0) {
                continue;
            }
            try {
                DB::table($table)->update(['created_at' => now()]);
                $this->fail("$table was updated");
            } catch (\Throwable $e) {
                $this->assertStringContainsString('append-only', $e->getMessage());
            }
            try {
                DB::table($table)->delete();
                $this->fail("$table was deleted");
            } catch (\Throwable $e) {
                $this->assertStringContainsString('append-only', $e->getMessage());
            }
        }
    }

    public function test_audit_chain_detects_tampering(): void
    {
        $this->activeLoan();
        $logger = app(\App\Services\AuditLogger::class);
        $this->assertTrue($logger->verifyChain());
        DB::unprepared('DROP TRIGGER audit_logs_no_delete');   // what an attacker with DDL rights would do first...
        DB::table('audit_logs')->where('id', \App\Models\AuditLog::query()->orderBy('id')->skip(2)->value('id'))->delete();
        $this->assertFalse($logger->verifyChain(), '...but a missing row breaks the hash chain');
    }
}
