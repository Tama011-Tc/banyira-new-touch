<?php

namespace Tests\Feature;

use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Services\ApplicationWorkflow;
use App\Services\AssessmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApplicationAssessmentTest extends TestCase
{
    public function test_business_creates_saves_uploads_and_submits(): void
    {
        $owner = $this->business();
        $product = LoanProduct::where('slug', 'working-capital')->first();

        $this->actingAs($owner)->post('/business/applications', ['loan_product_id' => $product->id])->assertRedirect();
        $app = LoanApplication::firstOrFail();
        $this->assertSame('draft', $app->status);

        $this->actingAs($owner)->post("/business/applications/{$app->id}/submit")->assertSessionHasErrors('application');

        $this->actingAs($owner)->put("/business/applications/{$app->id}", [
            'loan_product_id' => $product->id, 'amount' => '1500000', 'term_months' => 12, 'purpose' => 'Buy stock',
            'records' => [['period' => '2026-05', 'inflow' => '1800000', 'outflow' => '1300000']],
        ])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/business/applications/{$app->id}/documents", ['type' => 'Owner national ID', 'file' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf')])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/business/applications/{$app->id}/submit")->assertRedirect("/business/applications/{$app->id}");

        $app->refresh();
        $this->assertSame('submitted', $app->status);
        $this->assertSame(1, $app->financialRecords()->count());
        $this->assertSame(['draft', 'submitted'], $app->history()->reorder('id')->pluck('to_status')->all());
        $this->actingAs($owner)->put("/business/applications/{$app->id}", ['loan_product_id' => $product->id])->assertForbidden();
    }

    public function test_information_request_round_trip(): void
    {
        $owner = $this->business();
        $credit = $this->staff('credit_officer');
        $app = $this->submittedApplication($owner);

        $this->asStaff($credit)->post("/internal/credit/applications/{$app->id}/review")->assertRedirect();
        $this->asStaff($credit)->post("/internal/credit/applications/{$app->id}/information", ['message' => 'Send your stock list'])->assertRedirect();
        $this->assertSame('information_required', $app->refresh()->status);

        $req = $app->informationRequests()->first();
        $this->actingAs($owner)->post("/business/information-requests/{$req->id}", ['response' => 'Uploaded'])->assertRedirect();
        $this->assertSame('under_review', $app->refresh()->status);
    }

    public function test_score_gives_class_from_rules_and_officer_cannot_approve_own_work(): void
    {
        $owner = $this->business();
        $credit = $this->staff('credit_officer');
        $exec = $this->staff('executive');
        $app = $this->submittedApplication($owner);
        app(ApplicationWorkflow::class)->startReview($app, $credit);
        $as = app(AssessmentService::class);
        $a = $as->start($app->refresh(), $credit);

        $this->asStaff($credit)->put("/internal/credit/assessments/{$a->id}", ['scores' => $this->scores([4, 4, 3, 3, 3]), 'recommendation' => 'approve', 'comments' => 'OK'])->assertSessionHasNoErrors();
        $a->refresh();
        $this->assertEquals(71.0, (float) $a->total_score);   // 30*.8 + 25*.8 + 20*.6 + 15*.6 + 10*.6
        $this->assertSame('A', $a->suggested_class);

        $as->submit($a, $credit);
        $this->expectException(ValidationException::class);
        try {
            $as->decide($a->refresh(), true, $credit);
        } finally {
            $this->asStaff($exec)->post("/internal/approvals/assessments/{$a->id}", ['approve' => true])->assertRedirect();
            $this->assertSame('approved', $app->refresh()->status);
            $this->assertSame('A', $app->risk_class);
        }
    }

    public function test_class_limits_block_approval(): void
    {
        $owner = $this->business();
        $credit = $this->staff('credit_officer');
        $app = $this->submittedApplication($owner, '4000000', 12);      // class C allows at most MK3m
        app(ApplicationWorkflow::class)->startReview($app, $credit);
        $as = app(AssessmentService::class);
        $a = $as->start($app->refresh(), $credit);
        $as->save($a, ['scores' => $this->scores([3, 2, 2, 2, 2]), 'recommendation' => 'approve', 'comments' => 'Weak'], $credit);
        $this->assertSame('C', $a->refresh()->suggested_class);

        $this->expectException(ValidationException::class);
        $as->submit($a, $credit);
    }

    public function test_override_needs_a_reason(): void
    {
        $owner = $this->business();
        $credit = $this->staff('credit_officer');
        $app = $this->submittedApplication($owner);
        app(ApplicationWorkflow::class)->startReview($app, $credit);
        $as = app(AssessmentService::class);
        $a = $as->start($app->refresh(), $credit);
        $as->save($a, ['scores' => $this->scores([4, 4, 4, 4, 4]), 'final_class' => 'A+', 'recommendation' => 'approve', 'comments' => 'Strong'], $credit);

        try {
            $as->submit($a->refresh(), $credit);
            $this->fail('Override without reason was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('override_reason', $e->errors());
        }
    }

    public function test_invalid_transitions_are_refused(): void
    {
        $app = $this->submittedApplication($this->business());
        $this->expectException(ValidationException::class);
        app(ApplicationWorkflow::class)->transition($app, 'listed', null);
    }
}
