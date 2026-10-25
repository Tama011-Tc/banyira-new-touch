<?php

namespace Tests\Feature;

use App\Console\Commands\CloseCampaigns;
use App\Enums\Role;
use App\Models\ApprovalRequest;
use App\Models\Disbursement;
use App\Models\FeeSchedule;
use App\Models\Listing;
use App\Models\LoanAgreement;
use App\Models\PaymentAttempt;
use App\Services\FeeScheduleService;
use App\Services\FeeService;
use App\Services\PaymentService;
use App\Services\Settings;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FeeScheduleAndCampaignTest extends TestCase
{
    public function test_with_no_approved_schedule_no_fee_is_charged_anywhere(): void
    {
        FeeSchedule::query()->delete();
        $this->assertFalse(FeeService::snapshot(100000000)['approved']);
        $a = $this->agreement(null, null, 100000000);
        $this->assertSame(0, $a->fees['origination_bp'] + $a->fees['investor_service_bp'] + $a->fees['processing_bp']);
        $this->assertSame(100000000, app(PaymentService::class)->fundingAmount($a));
        $this->fund($a);
        $d = Disbursement::first();
        $this->assertSame([100000000, 0, 100000000], [$d->gross, $d->fees, $d->net]);
        $this->assertSame(0, \App\Models\PlatformFee::count(), 'no fee rows are created for fees that do not exist');
        $this->actingAs($this->user(Role::Investor))->get('/marketplace/'.$a->listing_id)->assertInertia(fn ($p) => $p->where('listing.fees.approved', false));
    }

    public function test_tiers_pick_the_fee_by_loan_size_with_fixed_charges_and_tax(): void
    {
        FeeSchedule::query()->delete();
        FeeSchedule::create(['name' => 'Tiered', 'effective_from' => now()->subDay()->toDateString(), 'tax_bp' => 1650, 'processing_bp' => 100, 'tiers' => [
            ['min' => 0, 'max' => 100000000, 'origination_bp' => 1500, 'origination_fixed' => 50000, 'investor_bp' => 0, 'investor_fixed' => 0],
            ['min' => 100000000, 'max' => null, 'origination_bp' => 400, 'origination_fixed' => 0, 'investor_bp' => 100, 'investor_fixed' => 10000],
        ]]);
        $small = FeeService::snapshot(50000000);
        $big = FeeService::snapshot(100000000);                       // boundary belongs to the upper tier
        $this->assertSame([1500, 50000, 0], [$small['origination_bp'], $small['origination_fixed'], $small['investor_service_bp']]);
        $this->assertSame([400, 0, 100, 10000], [$big['origination_bp'], $big['origination_fixed'], $big['investor_service_bp'], $big['investor_service_fixed']]);
        $f = FeeService::onPrincipal($small, 50000000);
        $this->assertSame(7500000 + 50000, $f['origination']);        // 15% + MK500
        $this->assertSame((int) round(7550000 * 0.165), $f['origination_tax']);
        $this->assertSame(50000000 - 7550000 - $f['origination_tax'], $f['net_to_business']);
    }

    public function test_tier_validation_rejects_gaps_overlaps_and_open_ends(): void
    {
        $svc = app(FeeScheduleService::class);
        $row = fn ($min, $max) => ['min' => $min, 'max' => $max, 'origination_bp' => 100, 'origination_fixed' => 0, 'investor_bp' => 0, 'investor_fixed' => 0];
        foreach ([[], [$row(100, null)], [$row(0, 500), $row(600, null)], [$row(0, 500), $row(400, null)], [$row(0, 500)], [$row(0, 0), $row(0, null)]] as $bad) {
            try {
                $svc->validateTiers($bad);
                $this->fail('should be refused: '.json_encode($bad));
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->assertCount(2, $svc->validateTiers([$row(0, 500), $row(500, null)]));
    }

    public function test_a_future_dated_schedule_is_not_used_early_and_the_old_one_retires(): void
    {
        $exec = $this->user(Role::Executive);
        $svc = app(FeeScheduleService::class);
        $tier = fn ($bp) => [['min' => 0, 'max' => null, 'origination_bp' => $bp, 'origination_fixed' => 0, 'investor_bp' => 0, 'investor_fixed' => 0]];
        $svc->publish(['name' => 'Next quarter', 'effective_from' => now()->addDays(30)->toDateString(), 'tax_bp' => 0, 'processing_bp' => 0, 'tiers' => $tier(777)], $exec);
        $this->assertSame(200, FeeService::snapshot(1000)['origination_bp']);
        $this->assertSame(777, $svc->active(now()->addDays(31)->toImmutable())->tiers[0]['origination_bp']);
        $this->assertSame(now()->addDays(29)->toDateString(), FeeSchedule::where('name', 'Test schedule')->first()->effective_to->toDateString());
    }

    public function test_only_finance_style_roles_can_propose_and_the_proposal_validates(): void
    {
        $body = ['name' => 'X', 'effective_from' => now()->toDateString(), 'tax_bp' => 0, 'processing_bp' => 100, 'reason' => 'A proper reason for the change.', 'tiers' => [['min' => '0', 'max' => '', 'origination_bp' => 500, 'investor_bp' => 0]]];
        foreach ([Role::Investor, Role::CreditApprover, Role::ItAdmin, Role::Sales] as $r) {
            $this->actingAs($this->user($r))->post('/finance/fee-schedules', $body)->assertForbidden();
        }
        $f = $this->actingAs($this->user(Role::Finance));
        $f->post('/finance/fee-schedules', ['tiers' => [['min' => '0', 'max' => '', 'origination_bp' => 9999, 'investor_bp' => 0]]] + $body)->assertSessionHasErrors('tiers.0.origination_bp');
        $f->post('/finance/fee-schedules', ['effective_from' => '2001-01-01'] + $body)->assertSessionHasErrors('effective_from');
        $f->post('/finance/fee-schedules', ['tiers' => [['min' => '10', 'max' => '', 'origination_bp' => 500, 'investor_bp' => 0]]] + $body)->assertSessionHasErrors('tiers');
        $f->post('/finance/fee-schedules', $body)->assertSessionHasNoErrors();
    }

    // ---- campaign minimum
    public function test_the_business_is_not_paid_until_the_campaign_reaches_its_minimum(): void
    {
        $listing = $this->listing(['amount' => 400000000]);
        $a = $this->agreement($listing, null, 100000000);             // 25% of the target
        $this->fund($a);
        $pay = app(PaymentService::class);
        $d = Disbursement::first();
        $fin = $this->user(Role::Finance);
        try {
            $pay->approveDisbursement($d, $fin);
            $this->fail('should wait for the campaign');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('campaign', $e->errors());
        }
        $this->assertSame('pending_approval', $d->fresh()->status);
        $this->actingAs($fin)->get('/finance')->assertInertia(fn ($p) => $p->where('disbursements.0.funded_pct', 25)->where('disbursements.0.ready', false));

        // the same campaign becomes payable once enough is funded
        $b = $this->agreement($listing, $this->user(Role::Investor), 300000000);
        $this->fund($b);
        $pay->approveDisbursement(Disbursement::where('agreement_id', $a->id)->first(), $fin);
        $this->assertSame('active', $a->fresh()->status);
    }

    public function test_the_minimum_is_configurable(): void
    {
        Settings::set('campaign.min_funded_pct', 25);
        $listing = $this->listing(['amount' => 400000000]);
        $a = $this->agreement($listing, null, 100000000);
        $this->fund($a);
        app(PaymentService::class)->approveDisbursement(Disbursement::first(), $this->user(Role::Finance));
        $this->assertSame('active', $a->fresh()->status);
    }

    public function test_a_campaign_that_misses_the_minimum_is_closed_and_refunds_are_queued_for_approval(): void
    {
        $listing = $this->listing(['amount' => 400000000]);
        $paid = $this->agreement($listing, null, 100000000);
        $this->fund($paid);
        $unpaid = $this->agreement($listing, $this->user(Role::Investor), 100000000);       // agreed but never paid
        $this->travel(25)->days();

        $this->artisan('banyira:close-campaigns')->assertSuccessful();
        $this->assertSame('closed', $listing->fresh()->status);
        $this->assertSame('expired', $listing->application->fresh()->status);
        $this->assertSame('cancelled', $unpaid->fresh()->status);
        $this->assertSame('cancelled', Disbursement::where('agreement_id', $paid->id)->first()->status);
        $req = ApprovalRequest::where('type', 'reversal')->first();
        $this->assertSame(0, $req->requested_by);                                          // raised by the system, decided by people
        $this->assertSame($paid->id, PaymentAttempt::find($req->payload['attempt_id'])->agreement_id);
        $this->assertSame('succeeded', PaymentAttempt::find($req->payload['attempt_id'])->status, 'nothing moves until a person approves');

        // finance approves and executes the refund as usual
        $this->actingAs($this->user(Role::Finance))->post("/finance/approvals/{$req->id}", ['approve' => true])->assertSessionHasNoErrors();
        $this->actingAs($this->user(Role::Compliance))->post("/finance/approvals/{$req->id}/execute")->assertSessionHasNoErrors();
        $this->assertSame('reversed', PaymentAttempt::find($req->payload['attempt_id'])->status);

        $this->artisan('banyira:close-campaigns')->assertSuccessful();                      // idempotent
        $this->assertSame(1, ApprovalRequest::where('type', 'reversal')->count());
        $this->actingAs($this->user(Role::Investor))->get('/marketplace')->assertInertia(fn ($p) => $p->has('listings.data', 0));
    }

    public function test_a_campaign_that_reached_the_minimum_closes_as_funded(): void
    {
        Settings::set('campaign.min_funded_pct', 50);
        $listing = $this->listing(['amount' => 200000000]);
        $a = $this->agreement($listing, null, 100000000);
        $this->fund($a);
        $this->travel(25)->days();
        $this->artisan('banyira:close-campaigns')->assertSuccessful();
        $this->assertSame('funded', $listing->fresh()->status);
        $this->assertSame('funded', $listing->application->fresh()->status);
        $this->assertSame(0, ApprovalRequest::count());
        app(PaymentService::class)->approveDisbursement(Disbursement::first(), $this->user(Role::Finance));
        $this->assertSame('active', $a->fresh()->status);
    }

    public function test_open_campaigns_before_their_deadline_are_left_alone(): void
    {
        $listing = $this->listing();
        $this->artisan('banyira:close-campaigns')->assertSuccessful();
        $this->assertSame('published', $listing->fresh()->status);
    }
}
