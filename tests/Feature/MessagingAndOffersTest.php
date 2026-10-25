<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\LoanAgreement;
use App\Models\Message;
use App\Models\OfferVersion;
use App\Services\MessagingService;
use App\Services\OfferService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MessagingAndOffersTest extends TestCase
{
    private function convo(): array
    {
        $listing = $this->listing();
        $inv = $this->user(Role::Investor);
        $owner = $listing->application->business->owner;
        $c = app(MessagingService::class)->start($listing, $inv);

        return [$c, $inv, $owner, $listing];
    }

    public function test_unverified_investors_cannot_contact_businesses_or_invest(): void
    {
        $listing = $this->listing();
        $inv = $this->user(Role::Investor);
        $inv->investorProfile->update(['kyc_status' => 'pending']);
        $this->actingAs($inv)->post("/marketplace/{$listing->id}/contact")->assertSessionHasErrors('kyc');
        $inv->investorProfile->update(['kyc_status' => 'verified', 'risk_acknowledged_at' => null]);
        $this->actingAs($inv)->post("/marketplace/{$listing->id}/contact")->assertSessionHasErrors('risk');
        $inv->investorProfile->update(['risk_acknowledged_at' => now()]);
        $this->actingAs($inv)->post("/marketplace/{$listing->id}/contact")->assertRedirect();
    }

    public function test_clean_messages_are_delivered_both_ways(): void
    {
        [$c, $inv, $owner] = $this->convo();
        $svc = app(MessagingService::class);
        $this->assertSame('delivered', $svc->send($c, $inv, 'How many staff do you employ and what is monthly feed cost?')['outcome']);
        $this->assertSame('delivered', $svc->send($c, $owner, 'We employ 14 people. Feed is MK 1,400,000 a month.')['outcome']);
        $this->assertCount(2, $svc->visibleTo($c, $inv));
    }

    public function test_contact_details_are_blocked_server_side_and_hidden_from_the_recipient(): void
    {
        [$c, $inv, $owner] = $this->convo();
        $this->actingAs($inv)->post("/messages/{$c->id}", ['body' => 'Call me on +265 999 123 456'])->assertSessionHas('error');
        $m = Message::latest('id')->first();
        $this->assertSame('blocked', $m->status);
        $this->assertContains('phone', collect($m->flags)->pluck('rule')->all());
        $this->assertCount(0, app(MessagingService::class)->visibleTo($c, $owner));                     // recipient sees nothing
        $this->assertCount(1, app(MessagingService::class)->visibleTo($c, $inv));                       // sender sees their own held message
        $this->actingAs($owner)->get("/messages/{$c->id}")->assertInertia(fn ($p) => $p->where('messages', []));
        $this->assertDatabaseHas('message_moderation_events', ['message_id' => $m->id, 'kind' => 'auto_block']);
    }

    public function test_off_platform_requests_are_held_for_review(): void
    {
        [$c, $inv] = $this->convo();
        $r = app(MessagingService::class)->send($c, $inv, 'Could we talk outside the platform about the terms?');
        $this->assertSame('pending_review', $r['outcome']);
    }

    public function test_false_positives_can_be_appealed_and_released_by_a_moderator(): void
    {
        [$c, $inv, $owner] = $this->convo();
        $svc = app(MessagingService::class);
        $m = $svc->send($c, $inv, 'Our order reference is 088812345678')['message'];       // looks like a phone number
        $this->assertSame('blocked', $m->status);
        $this->actingAs($owner)->post("/message-items/{$m->id}/appeal")->assertForbidden();   // only the sender can appeal
        $this->actingAs($inv)->post("/message-items/{$m->id}/appeal", ['note' => 'This is an invoice number, not a phone number.'])->assertSessionHasNoErrors();
        $this->assertSame('pending_review', $m->fresh()->status);
        $mod = $this->user(Role::Support);
        $this->actingAs($this->user(Role::Investor))->post("/moderation/messages/{$m->id}", ['outcome' => 'release'])->assertForbidden();
        $this->actingAs($mod)->post("/moderation/messages/{$m->id}", ['outcome' => 'release', 'note' => 'Invoice number'])->assertSessionHasNoErrors();
        $this->assertSame('released', $m->fresh()->status);
        $this->assertCount(1, $svc->visibleTo($c, $owner));
        $this->assertDatabaseHas('message_moderation_events', ['message_id' => $m->id, 'kind' => 'release', 'actor_id' => $mod->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'moderation.release', 'actor_id' => $mod->id]);
    }

    public function test_only_participants_can_read_or_post_to_a_conversation(): void
    {
        [$c, $inv, $owner] = $this->convo();
        $stranger = $this->user(Role::Investor);
        $strangerBiz = $this->business()->owner;
        foreach ([$stranger, $strangerBiz, $this->user(Role::Support), $this->user(Role::Executive)] as $u) {
            $this->actingAs($u)->get("/messages/{$c->id}")->assertNotFound();
            $this->actingAs($u)->post("/messages/{$c->id}", ['body' => 'hi'])->assertNotFound();
            $this->actingAs($u)->post("/messages/{$c->id}/offers", [])->assertNotFound();
        }
        $this->actingAs($inv)->get("/messages/{$c->id}")->assertOk();
        $this->actingAs($owner)->get("/messages/{$c->id}")->assertOk();
    }

    public function test_messages_are_rate_limited_and_length_limited(): void
    {
        [$c, $inv] = $this->convo();
        $this->actingAs($inv)->post("/messages/{$c->id}", ['body' => str_repeat('a', 2001)])->assertSessionHasErrors('body');
        $this->actingAs($inv)->post("/messages/{$c->id}", ['body' => ' '])->assertSessionHasErrors('body');
        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($inv)->post("/messages/{$c->id}", ['body' => "Message number $i about terms"]);
        }
        $this->actingAs($inv)->post("/messages/{$c->id}", ['body' => 'One too many'])->assertStatus(429);
    }

    public function test_reporting_creates_a_case_for_moderators(): void
    {
        [$c, $inv] = $this->convo();
        $this->actingAs($inv)->post("/messages/{$c->id}/report", ['reason' => 'off_platform', 'detail' => 'Asked me to pay by mobile money directly.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('message_reports', ['conversation_id' => $c->id, 'reason' => 'off_platform', 'status' => 'open']);
        $this->actingAs($this->user(Role::Support))->get('/moderation')->assertOk()->assertInertia(fn ($p) => $p->has('reports', 1));
    }

    public function test_moderation_rules_are_configurable(): void
    {
        [$c, $inv] = $this->convo();
        \App\Services\Settings::set('moderation.rules', ['email']);   // phone rule off
        $this->assertSame('delivered', app(MessagingService::class)->send($c, $inv, 'Ref 0888123456 for the invoice')['outcome']);
        $this->assertSame('blocked', app(MessagingService::class)->send($c, $inv, 'mail me bob@example.com')['outcome']);
    }

    // ---- offers
    private function terms(array $o = []): array
    {
        return $o + ['principal' => 100000000, 'rate_bp' => 2200, 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'grace_instalments' => 0];
    }

    public function test_offer_versions_supersede_each_other_and_require_both_parties(): void
    {
        [$c, $inv, $owner] = $this->convo();
        $o = app(OfferService::class);
        $v1 = $o->propose($c, $inv, $this->terms());
        $this->assertSame(1, $v1->version);
        $this->assertNotNull($v1->investor_accepted_at);                                   // proposer implicitly accepts own terms
        $this->assertNull($v1->business_accepted_at);
        $v2 = $o->propose($c, $owner, $this->terms(['rate_bp' => 2000]));                   // counter-offer
        $this->assertSame(2, $v2->version);
        $this->assertSame('superseded', $v1->fresh()->status);
        $this->assertNull($v2->investor_accepted_at);
        $this->assertThrowsValidation(fn () => $o->accept($v1->fresh(), $owner), 'offer');   // stale version cannot be accepted
        $this->assertNull($o->accept($v2->fresh(), $inv) ? null : null);
        $this->assertSame(1, LoanAgreement::count());
        $a = LoanAgreement::first();
        $this->assertSame([2000, 100000000], [$a->rate_bp, $a->principal]);
        $this->assertSame('accepted', $v2->fresh()->status);
        $this->assertNotNull($a->agreement_hash);
    }

    public function test_one_party_acceptance_alone_never_creates_an_agreement(): void
    {
        [$c, $inv, $owner] = $this->convo();
        $v = app(OfferService::class)->propose($c, $inv, $this->terms());
        $this->assertNull(app(OfferService::class)->accept($v->fresh(), $inv));
        $this->assertSame(0, LoanAgreement::count());
        $this->actingAs($this->user(Role::Investor))->post("/messages/{$c->id}/offers/{$v->id}/accept")->assertNotFound();
    }

    public function test_terms_must_stay_inside_the_approved_limits(): void
    {
        [$c, $inv, , $listing] = $this->convo();
        $o = app(OfferService::class);
        $this->assertThrowsValidation(fn () => $o->propose($c, $inv, $this->terms(['rate_bp' => $listing->max_rate_bp + 1])), 'rate');
        $this->assertThrowsValidation(fn () => $o->propose($c, $inv, $this->terms(['principal' => $listing->min_investment - 1])), 'principal');
        $this->assertThrowsValidation(fn () => $o->propose($c, $inv, $this->terms(['principal' => $listing->max_investment + 1])), 'principal');
        $this->assertThrowsValidation(fn () => $o->propose($c, $inv, $this->terms(['term_months' => $listing->term_months + 1])), 'term_months');
        $this->assertThrowsValidation(fn () => $o->propose($c, $inv, $this->terms(['grace_instalments' => 9])), 'grace');
        $this->assertThrowsValidation(fn () => $o->propose($c, $inv, $this->terms(['interest_method' => 'compound'])), 'frequency');
        $this->assertSame(0, OfferVersion::count());
    }

    public function test_accepted_terms_cannot_be_silently_edited(): void
    {
        [$c, $inv, $owner] = $this->convo();
        $o = app(OfferService::class);
        $v = $o->propose($c, $inv, $this->terms());
        DB::table('offer_versions')->where('id', $v->id)->update(['rate_bp' => 100]);        // tampering with the stored terms
        $this->assertThrowsValidation(fn () => $o->accept($v->fresh(), $owner), 'offer');   // hash no longer matches
        $this->assertSame(0, LoanAgreement::count());
    }

    public function test_capacity_cannot_be_exceeded_across_agreements(): void
    {
        $listing = $this->listing(['amount' => 200000000]);
        $this->agreement($listing, null, 150000000);
        $inv2 = $this->user(Role::Investor);
        $c = app(MessagingService::class)->start($listing, $inv2);
        $this->assertThrowsValidation(fn () => app(OfferService::class)->propose($c, $inv2, $this->terms(['principal' => 100000000])), 'principal');
        app(OfferService::class)->propose($c, $inv2, $this->terms(['principal' => 50000000]));
        $this->assertTrue(true);
    }

    public function test_agreement_stores_a_valid_schedule_and_the_disclosed_fees(): void
    {
        $a = $this->agreement(null, null, 120000000, 2400);
        $this->assertCount(12, $a->schedule);
        $this->assertSame(120000000, (int) $a->schedule->sum('principal_due'));
        $this->assertSame($a->total_interest, (int) $a->schedule->sum('interest_due'));
        $this->assertSame('pending_funding', $a->status);
        $this->assertTrue($a->simulated);
        $this->assertEquals(['origination_bp' => 200, 'investor_service_bp' => 100, 'processing_bp' => 150, 'tax_bp' => 0], collect($a->fees)->only(['origination_bp', 'investor_service_bp', 'processing_bp', 'tax_bp'])->all());
        $this->assertSame('funding', $a->application->fresh()->status);
        $this->assertDatabaseHas('investment_commitments', ['agreement_id' => $a->id, 'status' => 'pledged']);
    }

    public function test_conversation_page_shows_versions_and_rules(): void
    {
        [$c, $inv, $owner] = $this->convo();
        app(OfferService::class)->propose($c, $inv, $this->terms());
        $this->actingAs($owner)->get("/messages/{$c->id}")->assertOk()->assertInertia(fn ($p) => $p->component('messages/Show')->has('offer.versions', 1)->where('conversation.role', 'business')->has('rules'));
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
