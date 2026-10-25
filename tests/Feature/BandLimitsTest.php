<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ApprovalRequest;
use App\Services\ApplicationWorkflow;
use App\Services\CreditService;
use App\Services\Settings;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Decision: average-risk (band C) businesses get tighter limits and written conditions; the platform rate ceiling is 36%. */
class BandLimitsTest extends TestCase
{
    private function assessedAs(string $band, array $appAttrs = []): array
    {
        $b = $this->business();
        $a = $this->application($b, $appAttrs);
        $analyst = $this->user(Role::CreditAnalyst);
        $credit = app(CreditService::class);
        app(ApplicationWorkflow::class)->submit($a, $b->owner);
        $credit->startScreening($a->fresh(), $analyst);
        $credit->beginAssessment($a->fresh(), $analyst);
        $credit->assess($a->fresh(), $analyst, $credit->suggest($a->fresh())['score'], 'Reviewed and consistent.', 'approve');
        \App\Models\CreditAssessment::where('application_id', $a->id)->update(['risk_category' => $band]);   // pin the band so each rule is tested on its own

        return [$a, $credit, $this->user(Role::CreditApprover)];
    }

    private function fails(callable $f, string $key): void
    {
        try {
            $f();
            $this->fail("expected an error on '$key'");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
        }
    }

    public function test_defaults_give_band_c_tighter_limits_and_leave_the_top_bands_alone(): void
    {
        $bands = collect(CreditService::bands())->keyBy('code');
        $this->assertSame([300000000, 12, true], [$bands['C']['max_amount'], $bands['C']['max_term_months'], $bands['C']['needs_conditions']]);
        $this->assertSame(24, $bands['B']['max_term_months']);
        $this->assertArrayNotHasKey('max_amount', $bands['A+']);
        $this->assertArrayNotHasKey('max_term_months', $bands['A']);
    }

    public function test_band_c_cannot_be_approved_above_its_amount_cap(): void
    {
        [$a, $credit, $approver] = $this->assessedAs('C', ['amount' => 500000000, 'term_months' => 12]);
        $this->fails(fn () => $credit->decide($a->fresh(), $approver, 'approved', 'Approve the full amount requested.', 'Monthly reporting to credit.'), 'approved_amount');
        $credit->decide($a->fresh(), $approver, 'approved', 'Approve a reduced amount with conditions.', 'Monthly sales report to credit staff.', 300000000);
        $this->assertSame(300000000, $a->fresh()->decisions()->first()->approved_amount);
    }

    public function test_band_c_loans_are_limited_to_twelve_months(): void
    {
        [$a, $credit, $approver] = $this->assessedAs('C', ['amount' => 200000000, 'term_months' => 18]);
        $this->fails(fn () => $credit->decide($a->fresh(), $approver, 'approved', 'Approve with the requested term.', 'Monthly reporting to credit.'), 'decision');
        $a->update(['term_months' => 12]);
        $credit->decide($a->fresh(), $approver, 'approved', 'Approve with the shortened term.', 'Monthly reporting to credit.');
        $this->assertSame('approved', $a->fresh()->status);
    }

    public function test_band_c_approvals_need_written_conditions(): void
    {
        [$a, $credit, $approver] = $this->assessedAs('C', ['amount' => 200000000, 'term_months' => 12]);
        $this->fails(fn () => $credit->decide($a->fresh(), $approver, 'approved', 'Approve without any conditions.'), 'conditions');
        $this->fails(fn () => $credit->decide($a->fresh(), $approver, 'approved', 'Approve with token conditions.', 'ok'), 'conditions');
        $credit->decide($a->fresh(), $approver, 'approved', 'Approve with real conditions.', 'Report sales monthly and keep records current.');
        $this->assertSame('approved', $a->fresh()->status);
    }

    public function test_band_b_has_a_term_cap_but_no_amount_cap_and_band_a_plus_has_none(): void
    {
        [$b, $cb, $ab] = $this->assessedAs('B', ['amount' => 900000000, 'term_months' => 30]);
        $this->fails(fn () => $cb->decide($b->fresh(), $ab, 'approved', 'Approve a thirty month term.'), 'decision');
        [$b2, $cb2, $ab2] = $this->assessedAs('B', ['amount' => 1400000000, 'term_months' => 24]);
        $cb2->decide($b2->fresh(), $ab2, 'approved', 'A large band B loan on a 24 month term.', null, null, 3000, null) ?? null;
        [$p, $cp, $ap] = $this->assessedAs('A+', ['amount' => 1400000000, 'term_months' => 36]);
        $this->assertNotNull($cp->decide($p->fresh(), $this->user(Role::Executive), 'approved', 'Top band with a long term is fine.', null, null, 3000));
    }

    public function test_rejecting_is_never_blocked_by_band_limits(): void
    {
        [$a, $credit, $approver] = $this->assessedAs('C', ['amount' => 900000000, 'term_months' => 36]);
        $credit->decide($a->fresh(), $approver, 'rejected', 'Too large and too long for this band.');
        $this->assertSame('rejected', $a->fresh()->status);
    }

    public function test_limits_are_editable_only_through_the_two_person_policy_change(): void
    {
        $bands = [
            ['code' => 'A+', 'min' => 85, 'name' => 'Very low risk', 'definition' => 'Top'],
            ['code' => 'C', 'min' => 40, 'name' => 'Average risk', 'definition' => 'Tighter', 'max_amount' => '2000000', 'max_term_months' => 9, 'needs_conditions' => true],
        ];
        $this->actingAs($this->user(Role::CreditApprover))->post('/credit-policy/bands', ['bands' => $bands, 'reason' => 'Tighten band C after portfolio review.'])->assertSessionHasNoErrors();
        $this->assertSame(300000000, collect(CreditService::bands())->firstWhere('code', 'C')['max_amount'], 'not applied yet');
        $req = ApprovalRequest::where('type', 'policy_change')->first();
        $this->actingAs($this->user(Role::Executive))->post("/finance/approvals/{$req->id}", ['approve' => true])->assertSessionHasNoErrors();
        $c = collect(CreditService::bands())->firstWhere('code', 'C');
        $this->assertSame([200000000, 9, true], [$c['max_amount'], $c['max_term_months'], $c['needs_conditions']]);
        $this->assertArrayNotHasKey('max_amount', collect(CreditService::bands())->firstWhere('code', 'A+'));
    }

    public function test_the_platform_rate_ceiling_is_36_percent_and_cannot_be_exceeded(): void
    {
        $this->assertSame(3600, Settings::get('loan.max_rate_bp'));
        $b = $this->business();
        $this->actingAs($b->owner)->post('/applications', ['business_id' => $b->id, 'amount' => '60000', 'purpose' => str_repeat('Buy stock for the season. ', 2), 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'proposed_rate' => 40, 'monthly_revenue' => '4000', 'monthly_expenses' => '2500', 'existing_debt' => '0', 'records_months' => 12])->assertSessionHasErrors('proposed_rate');
        [$a, $credit, $approver] = $this->assessedAs('A+');
        $this->fails(fn () => $credit->decide($a->fresh(), $approver, 'approved', 'A ceiling above the platform maximum.', null, null, 4000), 'max_rate_bp');
    }
}
