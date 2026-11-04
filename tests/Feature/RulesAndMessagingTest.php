<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\RuleSet;
use App\Services\RulesService;
use Tests\TestCase;

class RulesAndMessagingTest extends TestCase
{
    private function proposal(string $effective): array
    {
        return [
            'effective_from' => $effective, 'interest_ceiling' => '30', 'notes' => 'Lower ceiling',
            'fee_tiers' => [['upper_bound' => '1000000', 'rate' => '12'], ['upper_bound' => '5000000', 'rate' => '8'], ['upper_bound' => null, 'rate' => '4']],
            'bands' => [
                ['risk_class' => 'A+', 'label' => 'Very low risk', 'min_score' => 85, 'max_amount' => null, 'max_months' => null],
                ['risk_class' => 'A', 'label' => 'Low risk', 'min_score' => 70, 'max_amount' => null, 'max_months' => null],
                ['risk_class' => 'B', 'label' => 'Moderate risk', 'min_score' => 55, 'max_amount' => null, 'max_months' => 24],
                ['risk_class' => 'C', 'label' => 'Average risk', 'min_score' => 40, 'max_amount' => '3000000', 'max_months' => 12],
            ],
        ];
    }

    public function test_rule_changes_need_approval_by_someone_else_and_keep_history(): void
    {
        $it = $this->staff('it_admin');
        $exec = $this->staff('executive');

        $this->asStaff($it)->post('/internal/rules', $this->proposal(now()->toDateString()))->assertSessionHasNoErrors();
        $proposal = RuleSet::where('status', 'pending_approval')->firstOrFail();
        $this->assertSame('36.00', app(RulesService::class)->active()->interest_ceiling);   // unchanged until approved

        $this->asStaff($it)->post("/internal/approvals/rules/{$proposal->id}", ['approve' => true])->assertForbidden();
        $this->asStaff($exec)->post("/internal/approvals/rules/{$proposal->id}", ['approve' => true])->assertSessionHasNoErrors();

        $this->assertSame('30.00', app(RulesService::class)->active()->interest_ceiling);
        $this->assertSame('superseded', RuleSet::where('version', 'R-2025.1')->value('status'));
        $this->assertSame(2, RuleSet::count());
    }

    public function test_future_rules_wait_for_their_effective_date(): void
    {
        $it = $this->staff('it_admin');
        $exec = $this->staff('executive');
        $this->asStaff($it)->post('/internal/rules', $this->proposal(now()->addDays(10)->toDateString()));
        $p = RuleSet::where('status', 'pending_approval')->first();
        $this->asStaff($exec)->post("/internal/approvals/rules/{$p->id}", ['approve' => true]);

        $this->assertSame('approved', $p->refresh()->status);
        $this->assertSame('36.00', app(RulesService::class)->active()->interest_ceiling);
        $this->travel(11)->days();
        app(RulesService::class)->activateDue();
        $this->assertSame('30.00', app(RulesService::class)->active()->interest_ceiling);
    }

    public function test_invalid_fee_tiers_are_rejected(): void
    {
        $bad = $this->proposal(now()->toDateString());
        $bad['fee_tiers'] = [['upper_bound' => '5000000', 'rate' => '10'], ['upper_bound' => '1000000', 'rate' => '15'], ['upper_bound' => null, 'rate' => '4']];
        $this->asStaff($this->staff('it_admin'))->post('/internal/rules', $bad)->assertSessionHasErrors('fee_tiers');
    }

    public function test_messages_strip_contact_details_and_stay_private(): void
    {
        $owner = $this->business();
        $listing = $this->listedApplication($owner);
        $inv = $this->investor();

        $this->actingAs($inv)->post("/investor/marketplace/{$listing->id}/message", ['body' => 'Call me on 0999 123 456 or mail me@example.com'])->assertRedirect();
        $c = Conversation::firstOrFail();
        $m = $c->messages()->first();
        $this->assertTrue($m->redacted);
        $this->assertStringNotContainsString('0999', $m->body);
        $this->assertStringNotContainsString('me@example.com', $m->body);

        $this->actingAs($owner)->get("/business/messages/{$c->id}")->assertOk();
        $this->actingAs($this->investor())->get("/investor/messages/{$c->id}")->assertForbidden();
        $this->actingAs($this->investor())->post("/investor/messages/{$c->id}", ['body' => 'Hi'])->assertForbidden();
    }
}
