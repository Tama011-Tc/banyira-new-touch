<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Application;
use App\Models\Business;
use App\Models\SalesActivity;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use App\Services\CreditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SalesAndCaseFlowTest extends TestCase
{
    private function register(User $sales, string $email = 'owner@shop.test'): Business
    {
        $this->actingAs($sales)->post('/sales/register', ['name' => 'Shop Ltd', 'display_name' => 'Shop', 'sector' => 'Retail & trade', 'district' => 'Zomba', 'description' => str_repeat('A shop that sells goods locally. ', 2), 'years_operating' => 3, 'owner_name' => 'Shop Owner', 'owner_email' => $email, 'consent' => true])->assertSessionHasNoErrors();

        return Business::where('display_name', 'Shop')->latest('id')->first();
    }

    private function tokenFrom(string $link): array
    {
        parse_str(parse_url($link, PHP_URL_QUERY), $q);
        preg_match('#/reset-password/([^?]+)#', $link, $m);

        return [$m[1], $q['email']];
    }

    public function test_the_owner_gets_a_link_to_set_their_own_password_and_can_then_follow_progress(): void
    {
        $sales = $this->user(Role::Sales);
        $this->register($sales);
        $link = session('invite_link');
        $this->assertNotEmpty($link);
        $owner = User::where('email', 'owner@shop.test')->first();
        $this->assertSame($sales->id, $owner->invited_by);
        $this->assertSame(Role::BusinessOwner, $owner->role);

        [$token, $email] = $this->tokenFrom($link);
        auth()->logout();
        $this->post('/reset-password', ['token' => $token, 'email' => $email, 'password' => 'Owner-Chosen-Pass-1', 'password_confirmation' => 'Owner-Chosen-Pass-1'])->assertSessionHasNoErrors();
        $this->post('/login', ['email' => 'owner@shop.test', 'password' => 'Owner-Chosen-Pass-1'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertInertia(fn ($p) => $p->component('business/Overview')->where('business.display_name', 'Shop'));
        // the same link cannot be reused
        auth()->logout();
        $this->post('/reset-password', ['token' => $token, 'email' => $email, 'password' => 'Another-Pass-12345', 'password_confirmation' => 'Another-Pass-12345'])->assertSessionHasErrors('email');
    }

    public function test_the_rep_never_knows_or_sets_the_owners_password(): void
    {
        $sales = $this->user(Role::Sales);
        $this->register($sales);
        $owner = User::where('email', 'owner@shop.test')->first();
        foreach (['password', 'Password123', 'owner@shop.test', 'Shop Owner'] as $guess) {
            $this->assertFalse(\Illuminate\Support\Facades\Hash::check($guess, $owner->password));
        }
        $this->assertFalse((bool) $owner->must_change_password);
    }

    public function test_rep_prepares_an_application_and_uploads_documents_for_the_owner_who_then_sees_it(): void
    {
        Storage::fake('local');
        $sales = $this->user(Role::Sales);
        $b = $this->register($sales);
        $s = $this->actingAs($sales);
        $s->post('/applications', ['business_id' => $b->id, 'amount' => '30000', 'purpose' => str_repeat('Buy stock for the trading season. ', 2), 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'proposed_rate' => 24, 'monthly_revenue' => '2000', 'monthly_expenses' => '1200', 'existing_debt' => '0', 'records_months' => 12])->assertSessionHasNoErrors();
        $app = Application::first();
        $this->assertSame($sales->id, $app->assisted_by);
        foreach (config('banyira.documents.required') as $t) {
            $s->post("/applications/{$app->id}/documents", ['type' => $t, 'file' => UploadedFile::fake()->create("$t.pdf", 20, 'application/pdf')])->assertSessionHasNoErrors();
        }
        $this->assertSame(3, $app->documents()->count());
        $s->post("/applications/{$app->id}/submit")->assertSessionHasNoErrors();
        $this->assertSame('submitted', $app->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.uploaded', 'actor_id' => $sales->id]);

        // the owner can sign in (via the link) and sees the same application, its history and who helped
        $owner = $b->owner;
        $this->actingAs($owner)->get("/applications/{$app->id}")->assertOk()->assertInertia(fn ($p) => $p->where('application.status', 'submitted')->where('application.assisted_by_name', $sales->name)->has('application.documents', 3));
        $this->actingAs($owner)->get('/applications')->assertInertia(fn ($p) => $p->has('applications', 1));
        // another business owner cannot
        $this->actingAs($this->user(Role::BusinessOwner))->get("/applications/{$app->id}")->assertNotFound();
    }

    public function test_assisted_registration_needs_consent_and_a_unique_email(): void
    {
        $sales = $this->user(Role::Sales);
        $base = ['name' => 'Shop Ltd', 'display_name' => 'Shop', 'sector' => 'Retail & trade', 'district' => 'Zomba', 'description' => str_repeat('A shop that sells goods locally. ', 2), 'years_operating' => 3, 'owner_name' => 'Owner', 'owner_email' => 'dup@shop.test'];
        $this->actingAs($sales)->post('/sales/register', $base)->assertSessionHasErrors('consent');
        $this->assertSame(0, User::where('email', 'dup@shop.test')->count());
        $this->user(Role::Investor, ['email' => 'dup@shop.test']);
        $this->actingAs($sales)->post('/sales/register', $base + ['consent' => true])->assertSessionHasErrors('owner_email');
    }

    public function test_invite_only_leads_belong_to_the_rep_who_created_them(): void
    {
        $sales = $this->user(Role::Sales);
        $other = $this->user(Role::Sales);
        $this->actingAs($sales)->post('/sales/invite', ['owner_name' => 'Lead One', 'owner_email' => 'lead@x.test'])->assertSessionHas('invite_link');
        $lead = User::where('email', 'lead@x.test')->first();
        $this->assertSame($sales->id, $lead->invited_by);
        $this->actingAs($sales)->get("/sales/leads/{$lead->id}")->assertOk();
        $this->actingAs($other)->get("/sales/leads/{$lead->id}")->assertNotFound();
        $this->actingAs($other)->post("/sales/leads/{$lead->id}/invite")->assertNotFound();
        $this->actingAs($other)->post("/sales/leads/{$lead->id}/activities", ['kind' => 'note', 'body' => 'sneaky'])->assertNotFound();
        $this->actingAs($other)->get('/sales')->assertInertia(fn ($p) => $p->has('leads', 0));
        $this->actingAs($sales)->get('/sales')->assertInertia(fn ($p) => $p->has('leads', 1)->where('leads.0.status', 'invited'));
        // a non-lead user id is not reachable either
        $investor = $this->user(Role::Investor);
        $this->actingAs($sales)->get("/sales/leads/{$investor->id}")->assertNotFound();
    }

    public function test_a_lead_can_get_a_business_profile_added_only_with_consent(): void
    {
        $sales = $this->user(Role::Sales);
        $this->actingAs($sales)->post('/sales/invite', ['owner_name' => 'Lead', 'owner_email' => 'lead2@x.test']);
        $lead = User::where('email', 'lead2@x.test')->first();
        $biz = ['name' => 'L Ltd', 'display_name' => 'L Trading', 'sector' => 'Services', 'district' => 'Dedza', 'description' => str_repeat('Offers local services daily. ', 3), 'years_operating' => 2];
        $this->actingAs($sales)->post("/sales/leads/{$lead->id}/business", $biz)->assertSessionHasErrors('consent');
        $this->actingAs($sales)->post("/sales/leads/{$lead->id}/business", $biz + ['consent' => true])->assertRedirect();
        $b = Business::where('owner_id', $lead->id)->first();
        $this->assertSame([$sales->id, true], [$b->registered_by_staff_id, $b->owner_consent_at !== null]);
        $this->actingAs($sales)->post("/sales/leads/{$lead->id}/business", $biz + ['consent' => true])->assertStatus(422);   // only once
    }

    public function test_reinviting_replaces_the_old_link_and_stops_once_the_owner_has_signed_in(): void
    {
        $sales = $this->user(Role::Sales);
        $this->actingAs($sales)->post('/sales/invite', ['owner_name' => 'Lead', 'owner_email' => 'lead3@x.test']);
        $lead = User::where('email', 'lead3@x.test')->first();
        [$oldToken, $email] = $this->tokenFrom(session('invite_link'));
        $this->actingAs($sales)->post("/sales/leads/{$lead->id}/invite")->assertSessionHas('invite_link');
        [$newToken] = $this->tokenFrom(session('invite_link'));
        $this->assertNotSame($oldToken, $newToken);
        auth()->logout();
        $this->post('/reset-password', ['token' => $oldToken, 'email' => $email, 'password' => 'Old-Link-Pass-123', 'password_confirmation' => 'Old-Link-Pass-123'])->assertSessionHasErrors('email');
        $this->post('/reset-password', ['token' => $newToken, 'email' => $email, 'password' => 'New-Link-Pass-123', 'password_confirmation' => 'New-Link-Pass-123'])->assertSessionHasNoErrors();
        $this->post('/login', ['email' => $email, 'password' => 'New-Link-Pass-123']);
        auth()->logout();
        $this->actingAs($sales)->post("/sales/leads/{$lead->id}/invite")->assertSessionHas('error');    // already signed in
    }

    public function test_sales_can_log_interactions_and_follow_ups_privately(): void
    {
        $sales = $this->user(Role::Sales);
        $other = $this->user(Role::Sales);
        $this->actingAs($sales)->post('/sales/invite', ['owner_name' => 'Lead', 'owner_email' => 'lead4@x.test']);
        $lead = User::where('email', 'lead4@x.test')->first();
        $this->actingAs($sales)->post("/sales/leads/{$lead->id}/activities", ['kind' => 'onboarding_session', 'body' => 'Walk through the document checklist', 'due_on' => now()->addDays(3)->toDateString()])->assertSessionHasNoErrors();
        $this->actingAs($sales)->post("/sales/leads/{$lead->id}/activities", ['kind' => 'call', 'body' => 'x', 'due_on' => now()->subDays(3)->toDateString()])->assertSessionHasErrors(['body', 'due_on']);
        $a = SalesActivity::first();
        $this->actingAs($sales)->get('/sales')->assertInertia(fn ($p) => $p->has('follow_ups', 1));
        $this->actingAs($other)->post("/sales/activities/{$a->id}/done")->assertNotFound();
        $this->actingAs($sales)->post("/sales/activities/{$a->id}/done");
        $this->assertNotNull($a->fresh()->done_at);
        $this->actingAs($sales)->get('/sales')->assertInertia(fn ($p) => $p->has('follow_ups', 0));
    }

    // ---- the credit specialist's side
    public function test_submitting_notifies_every_active_analyst_and_nobody_else(): void
    {
        $a1 = $this->user(Role::CreditAnalyst);
        $a2 = $this->user(Role::CreditAnalyst);
        $off = $this->user(Role::CreditAnalyst);
        $off->forceFill(['status' => 'suspended'])->save();
        $approver = $this->user(Role::CreditApprover);
        $investor = $this->user(Role::Investor);
        $b = $this->business();
        $app = $this->application($b);
        app(ApplicationWorkflow::class)->submit($app, $b->owner);
        foreach ([$a1, $a2] as $a) {
            $this->assertSame(1, $a->unreadNotifications()->count());
            $this->assertStringContainsString("/credit/{$app->id}", $a->unreadNotifications->first()->data['url']);
        }
        foreach ([$off, $approver, $investor, $b->owner] as $nobody) {
            $this->assertSame(0, $nobody->unreadNotifications()->count());
        }
        $this->actingAs($a1)->get('/credit')->assertInertia(fn ($p) => $p->where('new_count', 1));
    }

    public function test_the_analyst_who_picks_up_a_case_owns_it_and_it_shows_in_their_queue(): void
    {
        $a1 = $this->user(Role::CreditAnalyst);
        $a2 = $this->user(Role::CreditAnalyst);
        $b = $this->business();
        $app = $this->application($b);
        app(ApplicationWorkflow::class)->submit($app, $b->owner);
        $this->actingAs($a1)->post("/credit/{$app->id}/advance", ['to' => 'screening'])->assertSessionHasNoErrors();
        $this->assertSame($a1->id, $app->fresh()->assigned_to);
        $this->actingAs($a1)->get('/credit?mine=1')->assertInertia(fn ($p) => $p->has('applications.data', 1)->where('applications.data.0.mine', true));
        $this->actingAs($a2)->get('/credit?mine=1')->assertInertia(fn ($p) => $p->has('applications.data', 0));
        $this->actingAs($a2)->get('/credit')->assertInertia(fn ($p) => $p->where('applications.data.0.assigned', $a1->name));
        $this->assertDatabaseHas('audit_logs', ['action' => 'credit.case_taken', 'actor_id' => $a1->id]);
    }

    public function test_the_applicant_and_the_assisting_rep_are_told_about_progress(): void
    {
        $sales = $this->user(Role::Sales);
        $b = $this->business();
        $app = $this->application($b, ['assisted_by' => $sales->id]);
        $analyst = $this->user(Role::CreditAnalyst);
        $approver = $this->user(Role::CreditApprover);
        $wf = app(ApplicationWorkflow::class);
        $credit = app(CreditService::class);
        $wf->submit($app, $sales);
        $credit->startScreening($app->fresh(), $analyst);
        $credit->requestInfo($app->fresh(), $analyst, 'Please upload the last six months of bank statements.');
        foreach ([$b->owner, $sales] as $who) {
            $titles = $who->unreadNotifications->pluck('data.title')->all();
            $this->assertContains('More information needed', $titles, $who->role->value);
        }
        $this->assertSame(0, $analyst->unreadNotifications()->where('data->title', 'More information needed')->count());
        $this->actingAs($b->owner)->get('/notifications')->assertOk()->assertInertia(fn ($p) => $p->has('items'));
        $this->actingAs($b->owner)->post('/notifications/read');
        $this->assertSame(0, $b->owner->fresh()->unreadNotifications()->count());
    }

    public function test_sales_still_cannot_reach_credit_or_approve_even_for_their_own_leads(): void
    {
        $sales = $this->user(Role::Sales);
        $b = $this->register($sales);
        $app = $this->application($b, ['assisted_by' => $sales->id]);
        app(ApplicationWorkflow::class)->submit($app, $sales);
        $this->actingAs($sales)->get('/credit')->assertForbidden();
        $this->actingAs($sales)->get("/credit/{$app->id}")->assertForbidden();
        $this->actingAs($sales)->post("/credit/{$app->id}/decide", ['decision' => 'approved', 'reason' => 'Approve my own lead please.'])->assertForbidden();
        $this->actingAs($sales)->post("/credit/{$app->id}/advance", ['to' => 'screening'])->assertForbidden();
    }
}
