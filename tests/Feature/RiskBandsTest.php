<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ApprovalRequest;
use App\Models\CreditAssessment;
use App\Services\CreditService;
use App\Services\Settings;
use Tests\TestCase;

class RiskBandsTest extends TestCase
{
    private function bands(array $over = []): array
    {
        return [
            ['code' => 'A+', 'min' => 90, 'name' => 'Top', 'definition' => 'Strongest'],
            ['code' => 'A', 'min' => 75, 'name' => 'Low', 'definition' => 'Low risk'],
            ['code' => 'B', 'min' => 50, 'name' => 'Medium', 'definition' => 'Medium risk'],
            ['code' => 'C', 'min' => 0, 'name' => 'High', 'definition' => 'High risk'],
        ] + $over;
    }

    public function test_default_bands_are_a_plus_a_b_c_and_map_scores_at_the_boundaries(): void
    {
        $this->assertSame(['A+', 'A', 'B', 'C'], array_column(CreditService::bands(), 'code'));
        foreach ([[100, 'A+'], [85, 'A+'], [84, 'A'], [70, 'A'], [69, 'B'], [55, 'B'], [54, 'C'], [40, 'C'], [39, 'NR'], [0, 'NR']] as [$score, $band]) {
            $this->assertSame($band, CreditService::category($score), "score $score");
        }
    }

    public function test_scores_below_the_lowest_band_are_not_rated_and_so_not_listable(): void
    {
        Settings::set('credit.bands', [['code' => 'X', 'min' => 40, 'name' => 'x', 'definition' => 'x'], ['code' => 'Y', 'min' => 10, 'name' => 'y', 'definition' => 'y']]);
        $this->assertSame('X', CreditService::category(80));
        $this->assertSame('Y', CreditService::category(10));
        $this->assertSame('NR', CreditService::category(9));
    }

    public function test_bands_are_changed_only_through_a_two_person_approval(): void
    {
        $approver = $this->user(Role::CreditApprover);
        $req = $this->actingAs($approver)->post('/credit-policy/bands', ['bands' => $this->bands(), 'reason' => 'Tighten the top band after review.']);
        $req->assertSessionHasNoErrors();
        $this->assertSame(85, CreditService::bands()[0]['min'], 'not applied until approved');
        $r = ApprovalRequest::where('type', 'policy_change')->first();
        $this->actingAs($approver)->post("/finance/approvals/{$r->id}", ['approve' => true])->assertForbidden();     // approver role cannot approve (not finance)
        $exec = $this->user(Role::Executive);
        $this->actingAs($exec)->post("/finance/approvals/{$r->id}", ['approve' => true])->assertSessionHasNoErrors();
        $this->assertSame(90, CreditService::bands()[0]['min']);
        $this->assertSame('A', CreditService::category(80));
        $this->assertDatabaseHas('audit_logs', ['action' => 'approval.approved', 'actor_id' => $exec->id]);
    }

    public function test_the_requester_cannot_approve_their_own_change(): void
    {
        $exec = $this->user(Role::Executive);
        $this->actingAs($exec)->post('/credit-policy/bands', ['bands' => $this->bands(), 'reason' => 'Change requested by an executive.'])->assertSessionHasNoErrors();
        $r = ApprovalRequest::first();
        $this->actingAs($exec)->post("/finance/approvals/{$r->id}", ['approve' => true])->assertSessionHasErrors('approval');
    }

    public function test_invalid_band_sets_are_rejected(): void
    {
        $a = $this->actingAs($this->user(Role::CreditApprover));
        $a->post('/credit-policy/bands', ['bands' => array_slice($this->bands(), 0, 1), 'reason' => 'Only one band is not enough.'])->assertSessionHasErrors('bands');
        $dupMin = $this->bands(); $dupMin[1]['min'] = 90;
        $a->post('/credit-policy/bands', ['bands' => $dupMin, 'reason' => 'Two bands start at the same score.'])->assertSessionHasErrors('bands');
        $dupCode = $this->bands(); $dupCode[1]['code'] = 'A+';
        $a->post('/credit-policy/bands', ['bands' => $dupCode, 'reason' => 'Two bands share a code.'])->assertSessionHasErrors('bands.1.code');
        $a->post('/credit-policy/bands', ['bands' => $this->bands(), 'reason' => 'short'])->assertSessionHasErrors('reason');
        $this->assertSame(0, ApprovalRequest::count());
    }

    public function test_only_credit_approvers_can_request_and_others_can_only_view(): void
    {
        foreach ([Role::CreditAnalyst, Role::Sales, Role::Investor, Role::ItAdmin, Role::Finance] as $r) {
            $this->actingAs($this->user($r))->post('/credit-policy/bands', ['bands' => $this->bands(), 'reason' => 'Trying to change policy.'])->assertForbidden();
        }
        $this->actingAs($this->user(Role::CreditAnalyst))->get('/credit-policy')->assertOk();
        $this->actingAs($this->user(Role::Investor))->get('/credit-policy')->assertForbidden();
    }

    public function test_existing_assessments_keep_their_band_when_policy_changes(): void
    {
        $listing = $this->listing();
        $a = CreditAssessment::where('application_id', $listing->application_id)->first();
        $before = [$a->risk_category, $listing->risk_category];
        Settings::set('credit.bands', [['code' => 'Z', 'min' => 0, 'name' => 'Everything', 'definition' => 'One band']]);
        $this->assertSame($before, [$a->fresh()->risk_category, $listing->fresh()->risk_category]);
        $this->assertSame('Z', CreditService::category(99));
    }

    public function test_the_marketplace_filters_and_shows_the_new_bands(): void
    {
        $listing = $this->listing();
        $investor = $this->user(Role::Investor);
        $band = $listing->risk_category;
        $this->actingAs($investor)->get('/marketplace?risk='.urlencode($band))->assertInertia(fn ($p) => $p->has('listings.data', 1)->has('risk_bands', 4));
        $other = collect(['A+', 'A', 'B', 'C'])->first(fn ($c) => $c !== $band);
        $this->actingAs($investor)->get('/marketplace?risk='.urlencode($other))->assertInertia(fn ($p) => $p->has('listings.data', 0));
    }
}
