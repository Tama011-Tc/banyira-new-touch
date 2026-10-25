<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Application;
use App\Models\Listing;
use App\Services\ApplicationWorkflow;
use App\Services\CreditService;
use App\Services\ListingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApplicationAndCreditTest extends TestCase
{
    public function test_drafts_save_and_submission_needs_fields_and_documents(): void
    {
        $b = $this->business();
        $a = $this->application($b, [], docs: false);
        $this->expectException(ValidationException::class);
        try {
            app(ApplicationWorkflow::class)->submit($a, $b->owner);
        } catch (ValidationException $e) {
            $this->assertCount(3, $e->errors()['application']);
            $this->assertSame('draft', $a->fresh()->status);
            throw $e;
        }
    }

    public function test_application_form_validation_and_ownership(): void
    {
        $b = $this->business();
        $this->actingAs($b->owner)->post('/applications', ['business_id' => $b->id])->assertSessionHasErrors(['amount', 'purpose', 'term_months', 'proposed_rate']);
        $this->actingAs($b->owner)->post('/applications', ['business_id' => $b->id, 'amount' => '60000.00', 'purpose' => str_repeat('Buy stock. ', 4), 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'proposed_rate' => 24, 'monthly_revenue' => '4000000', 'monthly_expenses' => '2500000', 'existing_debt' => '0'])->assertSessionHasNoErrors();
        $this->assertSame(6000000, Application::first()->amount);
        $other = $this->business();
        $this->actingAs($b->owner)->post('/applications', ['business_id' => $other->id] + ['amount' => '1', 'purpose' => str_repeat('x', 30), 'term_months' => 6, 'frequency' => 'monthly', 'interest_method' => 'flat', 'proposed_rate' => 10, 'monthly_revenue' => '1', 'monthly_expenses' => '1', 'existing_debt' => '0'])->assertNotFound();
    }

    public function test_rate_and_term_are_capped_by_platform_limits(): void
    {
        $b = $this->business();
        $this->actingAs($b->owner)->post('/applications', ['business_id' => $b->id, 'amount' => '100', 'purpose' => str_repeat('x', 30), 'term_months' => 999, 'frequency' => 'monthly', 'interest_method' => 'flat', 'proposed_rate' => 400, 'monthly_revenue' => '1', 'monthly_expenses' => '1', 'existing_debt' => '0'])->assertSessionHasErrors(['term_months', 'proposed_rate']);
    }

    public function test_document_upload_is_private_validated_and_hashed(): void
    {
        Storage::fake('local');
        $b = $this->business();
        $a = $this->application($b, [], docs: false);
        $u = $this->actingAs($b->owner);
        $u->post("/applications/{$a->id}/documents", ['type' => 'owner_id', 'file' => UploadedFile::fake()->create('id.exe', 10)])->assertSessionHasErrors('file');
        $u->post("/applications/{$a->id}/documents", ['type' => 'owner_id', 'file' => UploadedFile::fake()->create('id.pdf', 9000, 'application/pdf')])->assertSessionHasErrors('file');
        $u->post("/applications/{$a->id}/documents", ['type' => 'nonsense', 'file' => UploadedFile::fake()->create('id.pdf', 10, 'application/pdf')])->assertSessionHasErrors('type');
        $u->post("/applications/{$a->id}/documents", ['type' => 'owner_id', 'file' => UploadedFile::fake()->create('id.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $doc = $a->documents()->first();
        $this->assertNotNull($doc);
        $this->assertSame(64, strlen($doc->sha256));
        Storage::disk('local')->assertExists($doc->path);
        $this->assertStringNotContainsString('id.pdf', $doc->path);        // random stored name
        $this->assertStringStartsNotWith('public', $doc->path);
    }

    public function test_submitted_applications_cannot_be_edited_or_resubmitted_by_the_applicant(): void
    {
        $b = $this->business();
        $a = $this->application($b);
        app(ApplicationWorkflow::class)->submit($a, $b->owner);
        $this->actingAs($b->owner)->put("/applications/{$a->id}", ['amount' => '1'])->assertForbidden();
        $this->expectException(ValidationException::class);
        app(ApplicationWorkflow::class)->transition($a->fresh(), 'submitted', $b->owner);
    }

    public function test_invalid_state_jumps_are_refused(): void
    {
        $b = $this->business();
        $a = $this->application($b);
        $wf = app(ApplicationWorkflow::class);
        foreach (['approved', 'published', 'funding', 'repayment_active'] as $bad) {
            try {
                $wf->transition($a, $bad, $this->user(Role::Executive));
                $this->fail("draft -> $bad should be refused");
            } catch (ValidationException) {
                $this->assertSame('draft', $a->fresh()->status);
            }
        }
    }

    public function test_full_review_path_records_every_status_change(): void
    {
        $listing = $this->listing();
        $a = $listing->application->fresh();
        $this->assertSame('published', $a->status);
        $states = \App\Models\AuditLog::where('action', 'application.status')->where('subject_id', $a->id)->orderBy('id')->get()->map(fn ($l) => $l->meta['to'])->all();
        $this->assertSame(['submitted', 'screening', 'assessment', 'approved', 'listing_prepared', 'published'], $states);
    }

    public function test_decision_needs_assessment_reason_and_a_different_person(): void
    {
        $b = $this->business();
        $a = $this->application($b);
        $analyst = $this->user(Role::CreditAnalyst);
        $approver = $this->user(Role::CreditApprover);
        $wf = app(ApplicationWorkflow::class);
        $credit = app(CreditService::class);
        $wf->submit($a, $b->owner);
        $credit->startScreening($a->fresh(), $analyst);
        $credit->beginAssessment($a->fresh(), $analyst);

        $this->assertThrowsValidation(fn () => $credit->decide($a->fresh(), $approver, 'approved', 'Looks fine to me, go.'), 'assessment');
        $credit->assess($a->fresh(), $analyst, $credit->suggest($a->fresh())['score'], 'Cash flow and documents reviewed.', 'approve');
        $this->assertThrowsValidation(fn () => $credit->decide($a->fresh(), $approver, 'approved', 'short'), 'reason');
        // an analyst is not an approver at all
        try {
            $credit->decide($a->fresh(), $analyst, 'approved', 'I assessed it and I approve it.');
            $this->fail('analyst must not approve');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        // an executive who also authored the assessment cannot rubber-stamp it
        $exec = $this->user(Role::Executive);
        $a2 = $this->application($this->business());
        $wf->submit($a2, $a2->business->owner);
        $credit->startScreening($a2->fresh(), $analyst);
        $credit->beginAssessment($a2->fresh(), $analyst);
        \App\Models\CreditAssessment::create(['application_id' => $a2->id, 'analyst_id' => $exec->id, 'score' => 70, 'risk_category' => 'B', 'criteria' => [], 'notes' => 'x', 'recommendation' => 'approve']);
        $this->assertThrowsValidation(fn () => $credit->decide($a2->fresh(), $exec, 'approved', 'Same person assessed and approves.'), 'decision');
        $credit->decide($a->fresh(), $approver, 'approved', 'Meets policy, approved with conditions.', 'Share quarterly accounts');
        $this->assertSame('approved', $a->fresh()->status);
    }

    public function test_score_adjustments_are_bounded_and_explained(): void
    {
        $b = $this->business();
        $a = $this->application($b);
        $analyst = $this->user(Role::CreditAnalyst);
        app(ApplicationWorkflow::class)->submit($a, $b->owner);
        $credit = app(CreditService::class);
        $credit->startScreening($a->fresh(), $analyst);
        $credit->beginAssessment($a->fresh(), $analyst);
        $base = $credit->suggest($a->fresh());
        $this->assertSame(CreditService::category($base['score']), $base['category']);
        $this->assertSame(100, array_sum(array_column($base['criteria'], 'weight')));
        $this->assertThrowsValidation(fn () => $credit->assess($a->fresh(), $analyst, max(0, $base['score'] - 20), 'A long enough justification for a big change.', 'approve'), 'score');
        $this->assertThrowsValidation(fn () => $credit->assess($a->fresh(), $analyst, min(100, max(0, $base['score'] - 5)), 'short', 'approve'), 'notes');
    }

    public function test_threshold_requires_an_executive_and_rate_ceiling_is_enforced(): void
    {
        $listingApp = $this->application($this->business(), ['amount' => 2000000000, 'monthly_revenue' => 900000000, 'monthly_expenses' => 400000000]);
        $analyst = $this->user(Role::CreditAnalyst);
        $approver = $this->user(Role::CreditApprover);
        $exec = $this->user(Role::Executive);
        app(ApplicationWorkflow::class)->submit($listingApp, $listingApp->business->owner);
        $credit = app(CreditService::class);
        $credit->startScreening($listingApp->fresh(), $analyst);
        $credit->beginAssessment($listingApp->fresh(), $analyst);
        $credit->assess($listingApp->fresh(), $analyst, $credit->suggest($listingApp->fresh())['score'], 'Large facility, documents checked.', 'approve');
        $this->assertThrowsValidation(fn () => $credit->decide($listingApp->fresh(), $approver, 'approved', 'Approved by the standard approver.'), 'decision');
        $this->assertThrowsValidation(fn () => $credit->decide($listingApp->fresh(), $exec, 'approved', 'Executive approval of this facility.', null, null, 9000), 'max_rate_bp');
        $this->assertThrowsValidation(fn () => $credit->decide($listingApp->fresh(), $exec, 'approved', 'Executive approval of this facility.', null, 2500000000), 'approved_amount');
        $credit->decide($listingApp->fresh(), $exec, 'approved', 'Executive approval of this facility.', null, null, 3000);
        $this->assertSame('approved', $listingApp->fresh()->status);
    }

    public function test_material_terms_cannot_be_edited_after_approval(): void
    {
        $listing = $this->listing();
        $a = $listing->application->fresh();
        $this->assertThrowsValidation(fn () => app(CreditService::class)->amendTerms($a, $a->business->owner, ['amount' => 1]), 'terms');
        // while still in draft the same change is recorded and bumps the terms version
        $d = $this->application($this->business());
        app(CreditService::class)->amendTerms($d, $d->business->owner, ['amount' => 700000000]);
        $this->assertEquals([700000000, 2], [$d->fresh()->amount, $d->fresh()->terms_version]);
    }

    public function test_rejected_and_unlisted_applications_never_appear_to_investors(): void
    {
        $investor = $this->user(Role::Investor);
        $rejected = $this->application($this->business());
        $analyst = $this->user(Role::CreditAnalyst);
        $approver = $this->user(Role::CreditApprover);
        app(ApplicationWorkflow::class)->submit($rejected, $rejected->business->owner);
        $c = app(CreditService::class);
        $c->startScreening($rejected->fresh(), $analyst);
        $c->beginAssessment($rejected->fresh(), $analyst);
        $c->assess($rejected->fresh(), $analyst, $c->suggest($rejected->fresh())['score'], 'Cannot support repayment.', 'reject');
        $c->decide($rejected->fresh(), $approver, 'rejected', 'Repayment capacity is insufficient.');
        $approvedOnly = $this->application($this->business());
        $this->assertSame(0, Listing::count());
        $this->actingAs($investor)->get('/marketplace')->assertOk()->assertInertia(fn ($p) => $p->where('listings.data', []));
        $this->assertSame('rejected', $rejected->fresh()->status);
    }

    public function test_approved_applications_are_not_public_until_prepared_reviewed_and_published(): void
    {
        $b = $this->business();
        $a = $this->application($b);
        $analyst = $this->user(Role::CreditAnalyst);
        $approver = $this->user(Role::CreditApprover);
        $exec = $this->user(Role::Executive);
        app(ApplicationWorkflow::class)->submit($a, $b->owner);
        $c = app(CreditService::class);
        $c->startScreening($a->fresh(), $analyst);
        $c->beginAssessment($a->fresh(), $analyst);
        $c->assess($a->fresh(), $analyst, $c->suggest($a->fresh())['score'], 'All documents consistent.', 'approve');
        $c->decide($a->fresh(), $approver, 'approved', 'Meets policy on cover and documents.', null, null, 3000);
        $investor = $this->user(Role::Investor);
        $this->actingAs($investor)->get('/marketplace')->assertInertia(fn ($p) => $p->where('listings.data', []));

        $l = app(ListingService::class);
        $listing = $l->prepare($a->fresh(), $approver, ['title' => 'T', 'summary' => str_repeat('Fictional. ', 6), 'risk_disclosure' => str_repeat('Risk of loss. ', 5), 'min_investment' => 1000000, 'max_investment' => 100000000, 'deadline' => now()->addDays(9)->toDateString()]);
        $this->actingAs($investor)->get('/marketplace')->assertInertia(fn ($p) => $p->where('listings.data', []));
        $this->assertThrowsValidation(fn () => $l->review($listing, $approver), 'review');          // preparer cannot self-review
        $this->assertThrowsValidation(fn () => $l->publish($listing->fresh(), $exec), 'status');     // not reviewed yet
        $l->review($listing->fresh(), $exec);
        $l->publish($listing->fresh(), $exec);
        $this->actingAs($investor)->get('/marketplace')->assertInertia(fn ($p) => $p->has('listings.data', 1));
    }

    public function test_listing_exposes_no_confidential_fields(): void
    {
        $listing = $this->listing();
        $view = app(ListingService::class)->publicView($listing);
        $json = json_encode($view);
        foreach (['registration_no', 'contact_phone', 'owner_id', 'national_id', 'email', 'bank', 'monthly_revenue', 'existing_debt', 'Test Trading Ltd'] as $secret) {
            $this->assertStringNotContainsString($secret, $json, $secret);
        }
        $this->assertSame('Test Trading', $view['business']);
        $this->assertArrayHasKey('risk_disclosure', $view);
        $this->assertArrayHasKey('fees', $view);
    }

    public function test_listing_text_cannot_carry_contact_details(): void
    {
        $b = $this->business();
        $a = $this->application($b);
        $analyst = $this->user(Role::CreditAnalyst);
        $approver = $this->user(Role::CreditApprover);
        app(ApplicationWorkflow::class)->submit($a, $b->owner);
        $c = app(CreditService::class);
        $c->startScreening($a->fresh(), $analyst);
        $c->beginAssessment($a->fresh(), $analyst);
        $c->assess($a->fresh(), $analyst, $c->suggest($a->fresh())['score'], 'All documents consistent.', 'approve');
        $c->decide($a->fresh(), $approver, 'approved', 'Meets policy on cover and documents.');
        $this->assertThrowsValidation(fn () => app(ListingService::class)->prepare($a->fresh(), $approver, ['title' => 'Call 0888123456', 'summary' => str_repeat('x', 50), 'risk_disclosure' => str_repeat('y', 50), 'min_investment' => 1, 'max_investment' => 2, 'deadline' => now()->addDay()->toDateString()]), 'title');
    }

    public function test_sales_assisted_registration_records_the_rep_and_consent(): void
    {
        $sales = $this->user(Role::Sales);
        $this->actingAs($sales)->post('/sales/register', ['name' => 'Kiosk Ltd', 'display_name' => 'Kiosk', 'sector' => 'Retail & trade', 'district' => 'Blantyre', 'description' => str_repeat('A small kiosk selling goods. ', 3), 'years_operating' => 2, 'owner_name' => 'Owner', 'owner_email' => 'owner@kiosk.test'])->assertSessionHasErrors('consent');
        $this->actingAs($sales)->post('/sales/register', ['name' => 'Kiosk Ltd', 'display_name' => 'Kiosk', 'sector' => 'Retail & trade', 'district' => 'Blantyre', 'description' => str_repeat('A small kiosk selling goods. ', 3), 'years_operating' => 2, 'owner_name' => 'Owner', 'owner_email' => 'owner@kiosk.test', 'consent' => true])->assertSessionHasNoErrors();
        $b = \App\Models\Business::where('display_name', 'Kiosk')->first();
        $this->assertSame($sales->id, $b->registered_by_staff_id);
        $this->assertNotNull($b->owner_consent_at);
        $this->assertSame(Role::BusinessOwner, $b->owner->role);
        $this->assertDatabaseHas('audit_logs', ['action' => 'business.registered_by_staff', 'actor_id' => $sales->id]);
        // the rep sees only their own registrations
        $other = $this->business();
        $this->actingAs($sales)->get('/sales')->assertInertia(fn ($p) => $p->has('businesses', 1));
        $app = $this->application($b, ['assisted_by' => $sales->id]);
        $this->actingAs($sales)->get("/applications/{$app->id}")->assertOk();
        $this->actingAs($this->user(Role::Sales))->get("/applications/{$app->id}")->assertNotFound();
    }

    private function assertThrowsValidation(callable $fn, string $key): void
    {
        try {
            $fn();
            $this->fail("Expected a validation error on '$key'");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
        }
    }
}
