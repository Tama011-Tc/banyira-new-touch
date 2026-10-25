<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\LoanAgreement;
use App\Models\OfferVersion;
use App\Services\MessagingService;
use App\Services\OfferService;
use App\Services\Settings;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OfferLifecycleTest extends TestCase
{
    private function setup2(): array
    {
        $listing = $this->listing();
        $inv = $this->user(Role::Investor);
        $c = app(MessagingService::class)->start($listing, $inv);

        return [$c, $inv, $listing->application->business->owner, $listing];
    }

    private function terms(int $principal = 100000000): array
    {
        return ['principal' => $principal, 'rate_bp' => 2200, 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'grace_instalments' => 0];
    }

    public function test_proposals_carry_an_expiry_and_lapse(): void
    {
        [$c, $inv, $owner] = $this->setup2();
        $o = app(OfferService::class);
        $v = $o->propose($c, $inv, $this->terms());
        $this->assertSame(now()->addDays(7)->toDateString(), $v->expires_at->toDateString());
        $this->travel(8)->days();
        try {
            $o->accept($v->fresh(), $owner);
            $this->fail('expired');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('expired', $e->errors()['offer'][0]);
        }
        $this->assertSame('expired', $v->fresh()->status);
        $this->assertSame(0, LoanAgreement::count());
    }

    public function test_the_expiry_command_lapses_only_old_unanswered_proposals(): void
    {
        [$c, $inv, $owner] = $this->setup2();
        $v = app(OfferService::class)->propose($c, $inv, $this->terms());
        $this->artisan('banyira:expire-offers')->assertSuccessful();
        $this->assertSame('proposed', $v->fresh()->status);
        $this->travel(8)->days();
        $this->artisan('banyira:expire-offers')->assertSuccessful();
        $this->assertSame('expired', $v->fresh()->status);
    }

    public function test_the_lifetime_is_configurable(): void
    {
        Settings::set('offers.ttl_days', 1);
        [$c, $inv] = $this->setup2();
        $v = app(OfferService::class)->propose($c, $inv, $this->terms());
        $this->assertSame(now()->addDay()->toDateString(), $v->expires_at->toDateString());
    }

    public function test_only_the_proposer_can_withdraw_and_only_while_unanswered(): void
    {
        [$c, $inv, $owner] = $this->setup2();
        $o = app(OfferService::class);
        $v = $o->propose($c, $inv, $this->terms());
        $this->actingAs($owner)->post("/messages/{$c->id}/offers/{$v->id}/withdraw")->assertForbidden();
        $this->actingAs($this->user(Role::Investor))->post("/messages/{$c->id}/offers/{$v->id}/withdraw")->assertNotFound();
        $this->actingAs($inv)->post("/messages/{$c->id}/offers/{$v->id}/withdraw")->assertSessionHasNoErrors();
        $this->assertSame('withdrawn', $v->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'offer.withdrawn', 'actor_id' => $inv->id]);
        $this->expectException(ValidationException::class);
        $o->accept($v->fresh(), $owner);                                  // cannot accept a withdrawn proposal
    }

    public function test_an_agreed_offer_is_binding_and_cannot_be_withdrawn(): void
    {
        $a = $this->agreement();
        $v = $a->offerVersion;
        $this->actingAs($a->investor)->post("/messages/{$v->offer->conversation_id}/offers/{$v->id}/withdraw")->assertSessionHasErrors('offer');
        $this->assertSame('accepted', $v->fresh()->status);
        $this->assertSame('pending_funding', $a->fresh()->status);
    }

    public function test_a_personal_per_business_limit_is_enforced_on_proposal_and_acceptance(): void
    {
        [$c, $inv, $owner] = $this->setup2();
        $inv->investorProfile->update(['max_per_listing' => 150000000]);
        $o = app(OfferService::class);
        try {
            $o->propose($c, $inv, $this->terms(200000000));
            $this->fail('over the personal limit');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('principal', $e->errors());
        }
        $v = $o->propose($c, $inv, $this->terms(100000000));
        $o->accept($v->fresh(), $owner);                                  // within the limit
        // a second business conversation is a different listing, so it is not affected
        $this->assertSame(1, LoanAgreement::count());
    }

    public function test_a_platform_wide_total_cap_counts_every_loan_the_investor_has(): void
    {
        Settings::set('investor.max_total_commitment', 250000000);
        $inv = $this->user(Role::Investor);
        $this->agreement($this->listing(), $inv, 200000000);
        $listing2 = $this->listing();
        $c = app(MessagingService::class)->start($listing2, $inv);
        $o = app(OfferService::class);
        try {
            $o->propose($c, $inv, $this->terms(100000000));                 // 200m + 100m > 250m
            $this->fail('over the total cap');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('in total', $e->errors()['principal'][0]);
        }
        $o->propose($c, $inv, $this->terms(50000000));                      // 250m exactly is fine
        $this->assertSame(2, OfferVersion::count());
    }

    public function test_cancelled_agreements_stop_counting_toward_the_cap(): void
    {
        Settings::set('investor.max_total_commitment', 150000000);
        $inv = $this->user(Role::Investor);
        $a = $this->agreement($this->listing(), $inv, 150000000);
        $a->forceFill(['status' => 'cancelled'])->save();
        $c = app(MessagingService::class)->start($this->listing(), $inv);
        $this->assertNotNull(app(OfferService::class)->propose($c, $inv, $this->terms(150000000)));
    }
}
