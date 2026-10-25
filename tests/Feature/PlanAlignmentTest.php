<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Application;
use App\Models\CreditAssessment;
use App\Models\CreditDecision;
use App\Services\ApplicationWorkflow;
use App\Services\CreditService;
use App\Services\FeeScheduleService;
use App\Services\Settings;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** The business plan's explicit rules: eligibility, bands A+ to C only, and one simple 4-15% business fee. */
class PlanAlignmentTest extends TestCase
{
    private function atAssessment(array $appAttrs = [], array $bizAttrs = []): array
    {
        $b = $this->business(null, $bizAttrs);
        $a = $this->application($b, $appAttrs);
        $analyst = $this->user(Role::CreditAnalyst);
        $wf = app(ApplicationWorkflow::class);
        $credit = app(CreditService::class);
        $wf->submit($a, $b->owner);
        $credit->startScreening($a->fresh(), $analyst);
        $credit->beginAssessment($a->fresh(), $analyst);

        return [$a, $analyst, $credit];
    }

    // ---- eligibility
    public function test_eligibility_checks_follow_the_plan_six_months_of_records_and_a_year_of_trading(): void
    {
        [$a, , $credit] = $this->atAssessment(['records_months' => 6], ['years_operating' => 1]);
        $this->assertTrue($credit->eligibility($a->fresh())['met']);
        [$short, , $c2] = $this->atAssessment(['records_months' => 5], ['years_operating' => 1]);
        $e = $c2->eligibility($short->fresh());
        $this->assertFalse($e['met']);
        $this->assertSame([false, true], array_column($e['checks'], 'met') === [true, false] ? [false, true] : array_column($e['checks'], 'met'));
        [$new, , $c3] = $this->atAssessment(['records_months' => 24], ['years_operating' => 0]);
        $this->assertFalse($c3->eligibility($new->fresh())['checks'][0]['met']);
    }

    public function test_missing_the_usual_criteria_needs_a_written_exception_to_approve(): void
    {
        [$a, $analyst, $credit] = $this->atAssessment(['records_months' => 3]);
        $credit->assess($a->fresh(), $analyst, $credit->suggest($a->fresh())['score'], 'Strong cash flow despite the short record.', 'approve');
        $approver = $this->user(Role::CreditApprover);
        try {
            $credit->decide($a->fresh(), $approver, 'approved', 'Approve despite the short record of accounts.');
            $this->fail('needs an exception');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('eligibility', $e->errors());
        }
        try {
            $credit->decide($a->fresh(), $approver, 'approved', 'Approve despite the short record of accounts.', null, null, 3000, 'short');
            $this->fail('too short');
        } catch (ValidationException) {
        }
        $credit->decide($a->fresh(), $approver, 'approved', 'Approve despite the short record of accounts.', null, null, 3000, 'Seasonal trader with 3 years of mobile-money statements instead of ledgers.');
        $this->assertSame('approved', $a->fresh()->status);
        $this->assertStringContainsString('mobile-money', CreditDecision::first()->exception_reason);
    }

    public function test_a_fully_eligible_business_needs_no_exception_and_records_none(): void
    {
        [$a, $analyst, $credit] = $this->atAssessment();
        $credit->assess($a->fresh(), $analyst, $credit->suggest($a->fresh())['score'], 'Everything checks out and is consistent.', 'approve');
        $credit->decide($a->fresh(), $this->user(Role::CreditApprover), 'approved', 'Meets every criterion and policy.', null, null, 3000, 'ignored note that should not be stored');
        $this->assertNull(CreditDecision::first()->exception_reason);
    }

    public function test_the_exception_is_shown_to_credit_staff_and_the_form_collects_months_of_records(): void
    {
        $b = $this->business();
        $this->actingAs($b->owner)->post('/applications', ['business_id' => $b->id, 'amount' => '60000', 'purpose' => str_repeat('Buy stock for the season. ', 2), 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'proposed_rate' => 24, 'monthly_revenue' => '4000', 'monthly_expenses' => '2500', 'existing_debt' => '0'])->assertSessionHasErrors('records_months');
        $this->actingAs($b->owner)->post('/applications', ['business_id' => $b->id, 'amount' => '60000', 'purpose' => str_repeat('Buy stock for the season. ', 2), 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'proposed_rate' => 24, 'monthly_revenue' => '4000', 'monthly_expenses' => '2500', 'existing_debt' => '0', 'records_months' => 9])->assertSessionHasNoErrors();
        $app = Application::first();
        $this->assertSame(9, $app->records_months);
        $this->actingAs($this->user(Role::CreditAnalyst))->get("/credit/{$app->id}")->assertInertia(fn ($p) => $p->where('eligibility.checks.1.actual', '9 month(s)')->where('eligibility.met', true));
    }

    // ---- bands: nothing below C is listed
    public function test_a_score_below_the_lowest_band_cannot_be_approved(): void
    {
        [$a, $analyst, $credit] = $this->atAssessment();
        $low = max(0, $credit->suggest($a->fresh())['score'] - 15);
        Settings::set('credit.bands', [['code' => 'A', 'min' => 99, 'name' => 'Top', 'definition' => 'x'], ['code' => 'B', 'min' => 98, 'name' => 'Next', 'definition' => 'x']]);
        $credit->assess($a->fresh(), $analyst, $credit->suggest($a->fresh())['score'], 'Rated against a very strict scale.', 'approve');
        $this->assertSame('NR', CreditAssessment::first()->risk_category);
        try {
            $credit->decide($a->fresh(), $this->user(Role::CreditApprover), 'approved', 'Trying to approve an unrated business.');
            $this->fail('unrated');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cannot be listed', $e->errors()['decision'][0]);
        }
        $credit->decide($a->fresh(), $this->user(Role::CreditApprover), 'rejected', 'Below the lowest risk band, so not listable.');
        $this->assertSame('rejected', $a->fresh()->status);
    }

    public function test_default_bands_match_the_plans_names(): void
    {
        $bands = collect(CreditService::bands())->keyBy('code');
        $this->assertSame('Very low risk', $bands['A+']['name']);
        $this->assertSame('Average risk', $bands['C']['name']);
        $this->assertSame(['A+', 'A', 'B', 'C'], $bands->keys()->all());
    }

    // ---- fees: one simple 4-15% business charge and nothing else
    private function tiers(int $orig = 800, int $inv = 0, int $fixed = 0): array
    {
        return [['min' => 0, 'max' => null, 'origination_bp' => $orig, 'origination_fixed' => $fixed, 'investor_bp' => $inv, 'investor_fixed' => 0]];
    }

    public function test_the_business_service_charge_must_stay_between_four_and_fifteen_percent(): void
    {
        $svc = app(FeeScheduleService::class);
        foreach ([399, 1501, 0] as $bad) {
            try {
                $svc->validatePolicy($this->tiers($bad));
                $this->fail("$bad bp should be refused");
            } catch (ValidationException $e) {
                $this->assertStringContainsString('between 4% and 15%', $e->errors()['tiers'][0]);
            }
        }
        foreach ([400, 800, 1500] as $ok) {
            $svc->validatePolicy($this->tiers($ok));
        }
        $this->assertTrue(true);
    }

    public function test_investors_pay_no_fee_and_no_fixed_extras_are_allowed(): void
    {
        $svc = app(FeeScheduleService::class);
        $this->expectException(ValidationException::class);
        try {
            $svc->validatePolicy($this->tiers(800, 100));
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Investors are not charged', $e->errors()['tiers'][0]);
            $svc->validatePolicy($this->tiers(800, 0, 50000));      // a fixed extra: also refused
        }
    }

    public function test_a_policy_breaking_proposal_never_reaches_the_approvers_and_cannot_be_published_either(): void
    {
        $body = ['name' => 'X', 'effective_from' => now()->toDateString(), 'tax_bp' => 0, 'processing_bp' => 100, 'reason' => 'A proper reason for the change.', 'tiers' => [['min' => '0', 'max' => '', 'origination_bp' => 200, 'investor_bp' => 0]]];
        $this->actingAs($this->user(Role::Finance))->post('/finance/fee-schedules', $body)->assertSessionHasErrors('tiers');
        $this->assertSame(0, \App\Models\ApprovalRequest::count());
        $this->expectException(ValidationException::class);
        app(FeeScheduleService::class)->publish(['name' => 'Direct', 'effective_from' => now()->toDateString(), 'tax_bp' => 0, 'processing_bp' => 0, 'tiers' => $this->tiers(2000)], $this->user(Role::Executive));
    }

    public function test_the_plans_sectors_are_available(): void
    {
        foreach (['Agriculture', 'Manufacturing', 'Construction', 'Mining', 'Tourism'] as $s) {
            $this->assertContains($s, config('banyira.sectors'));
        }
    }
}
