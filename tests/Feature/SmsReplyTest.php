<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\LoanAgreement;
use App\Models\OfferVersion;
use App\Models\SmsInbound;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\MessagingService;
use App\Services\OfferService;
use App\Services\Settings;
use App\Services\Sms\SmsService;
use Tests\TestCase;

/** Business plan: the owner can accept or reject an investor's offer by sending a code by SMS. */
class SmsReplyTest extends TestCase
{
    private const OWNER_PHONE = '0888555444';

    private function scene(int $principal = 100000000): array
    {
        $listing = $this->listing();
        $owner = $listing->application->business->owner;
        $owner->business->update(['contact_phone' => self::OWNER_PHONE]);
        $inv = $this->user(Role::Investor);
        $inv->investorProfile->update(['phone' => '0999123456']);
        $c = app(MessagingService::class)->start($listing, $inv);
        $v = app(OfferService::class)->propose($c, $inv, ['principal' => $principal, 'rate_bp' => 2200, 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'grace_instalments' => 0]);

        return [$owner->fresh(), $inv->fresh(), $v, $c, $listing];
    }

    private function codeFor(User $u): string
    {
        preg_match('/Reply Y (\d{6}) to accept or N (\d{6})/', SmsMessage::where('user_id', $u->id)->where('kind', 'offer_received')->latest('id')->value('body'), $m);

        return $m[1];
    }

    private function reply(string $text, string $from = self::OWNER_PHONE, ?string $id = null): string
    {
        return app(\App\Services\Sms\SmsInboundService::class)->handle($id ?? 'evt-'.uniqid(), $from, $text);
    }

    public function test_the_owner_is_texted_the_terms_with_codes_and_can_accept_by_reply(): void
    {
        [$owner, $inv, $v] = $this->scene();
        $body = SmsMessage::where('user_id', $owner->id)->where('kind', 'offer_received')->value('body');
        $this->assertStringContainsString('MK 1,000,000', $body);
        $this->assertStringContainsString('22% a year over 12 months', $body);
        $code = $this->codeFor($owner);

        $this->assertSame('accepted', $this->reply("Y $code"));
        $this->assertSame(1, LoanAgreement::count());
        $this->assertSame('accepted', $v->fresh()->status);
        $this->assertNotNull($v->fresh()->business_accepted_at);
        $this->assertStringContainsString('Offer accepted', SmsMessage::where('user_id', $owner->id)->where('kind', 'reply')->latest('id')->value('body'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'sms.reply_accepted', 'actor_id' => $owner->id]);
        $this->assertSame('simulation', \App\Services\Platform::mode());
    }

    public function test_the_owner_can_decline_by_reply_and_nothing_is_created(): void
    {
        [$owner, $inv, $v] = $this->scene();
        $this->assertSame('declined', $this->reply('n '.$this->codeFor($owner)));
        $this->assertSame('declined', $v->fresh()->status);
        $this->assertSame(0, LoanAgreement::count());
    }

    public function test_formats_are_forgiving_but_the_code_must_be_exact(): void
    {
        [$owner, , $v] = $this->scene();
        $code = $this->codeFor($owner);
        $this->assertSame('not_understood', $this->reply('maybe '.$code));
        $this->assertSame('not_understood', $this->reply("Y $code extra"));
        $this->assertSame('not_understood', $this->reply('Y 12345'));
        $this->assertSame('invalid', $this->reply('Y '.($code === '000000' ? '111111' : '000000')));
        $this->assertSame('pending', $v->fresh()->status === 'proposed' ? 'pending' : 'changed');
        $this->assertSame('accepted', $this->reply('  yes'.$code.'  '));
    }

    public function test_a_stranger_or_the_wrong_number_changes_nothing_and_gets_no_reply(): void
    {
        [$owner, $inv, $v] = $this->scene();
        $code = $this->codeFor($owner);
        $before = SmsMessage::count();
        foreach (['0777000111', '+265 888 000 000', self::OWNER_PHONE === '0888555444' ? '0888555445' : '0888555444'] as $other) {
            $this->assertSame('unknown_sender', $this->reply("Y $code", $other));
        }
        $this->assertSame('unknown_sender', $this->reply("Y $code", '0999123456'), 'the investor’s own number cannot accept on the owner’s behalf');
        $this->assertSame($before, SmsMessage::count(), 'strangers get no response at all');
        $this->assertSame('proposed', $v->fresh()->status);
        $this->assertSame(0, LoanAgreement::count());
        $this->assertSame('unknown_sender', $this->reply("Y $code", 'garbage'));
    }

    public function test_another_business_owner_cannot_use_a_code_that_was_issued_to_someone_else(): void
    {
        [$owner, , $v] = $this->scene();
        $code = $this->codeFor($owner);
        $rival = $this->business(null, ['contact_phone' => '0888222333'])->owner;
        $this->assertSame('invalid', $this->reply("Y $code", '0888222333'));
        $this->assertSame('proposed', $v->fresh()->status);
    }

    public function test_a_code_works_once_and_a_replayed_gateway_event_is_ignored(): void
    {
        [$owner, , $v] = $this->scene();
        $code = $this->codeFor($owner);
        $this->assertSame('accepted', $this->reply("Y $code", self::OWNER_PHONE, 'evt-1'));
        $this->assertSame('duplicate', $this->reply("Y $code", self::OWNER_PHONE, 'evt-1'));       // same delivery again
        $this->assertSame('invalid', $this->reply("Y $code", self::OWNER_PHONE, 'evt-2'));         // same code, new message
        $this->assertSame(1, LoanAgreement::count());
        $this->assertSame(1, SmsInbound::where('outcome', 'accepted')->count());
    }

    public function test_codes_expire(): void
    {
        [$owner, , $v] = $this->scene();
        $code = $this->codeFor($owner);
        $this->travel(49)->hours();
        $this->assertSame('invalid', $this->reply("Y $code"));
        $this->assertSame(0, LoanAgreement::count());
    }

    public function test_a_newer_proposal_kills_the_old_code(): void
    {
        [$owner, $inv, $v, $c] = $this->scene();
        $old = $this->codeFor($owner);
        app(OfferService::class)->propose($c, $inv, ['principal' => 90000000, 'rate_bp' => 2000, 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'grace_instalments' => 0]);
        $this->assertSame('invalid', $this->reply("Y $old"));
        $new = $this->codeFor($owner);
        $this->assertNotSame($old, $new);
        $this->assertSame('accepted', $this->reply("Y $new"));
        $this->assertSame(90000000, LoanAgreement::first()->principal);                              // the version the code was issued for
    }

    public function test_a_withdrawn_offer_cannot_be_accepted_by_sms(): void
    {
        [$owner, $inv, $v] = $this->scene();
        $code = $this->codeFor($owner);
        app(OfferService::class)->withdraw($v->fresh(), $inv);
        $this->assertSame('invalid', $this->reply("Y $code"));
        $this->assertSame(0, LoanAgreement::count());
    }

    public function test_an_offer_on_a_closed_listing_cannot_be_accepted_by_sms(): void
    {
        [$owner, , $v, , $listing] = $this->scene();
        $code = $this->codeFor($owner);
        $listing->forceFill(['status' => 'closed'])->save();
        $this->assertSame('invalid', $this->reply("Y $code"));
        $this->assertSame(0, LoanAgreement::count());
    }

    public function test_two_businesses_sharing_a_number_are_treated_as_unknown(): void
    {
        [$owner] = $this->scene();
        $code = $this->codeFor($owner);
        $this->business(null, ['contact_phone' => self::OWNER_PHONE]);                              // someone else registers the same number
        $this->assertSame('unknown_sender', $this->reply("Y $code"));
        $this->assertSame(0, LoanAgreement::count());
    }

    public function test_large_offers_must_be_accepted_on_the_website(): void
    {
        Settings::set('sms.max_accept_amount', 50000000);
        [$owner, , $v] = $this->scene(100000000);
        $this->assertSame('needs_web', $this->reply('Y '.$this->codeFor($owner)));
        $this->assertSame('proposed', $v->fresh()->status);
        $this->assertStringContainsString('too large', SmsMessage::where('user_id', $owner->id)->where('kind', 'reply')->latest('id')->value('body'));
        // the website still works
        $this->actingAs($owner)->post("/messages/{$v->offer->conversation_id}/offers/{$v->id}/accept")->assertSessionHasNoErrors();
        $this->assertSame(1, LoanAgreement::count());
    }

    public function test_guessing_is_rate_limited_per_number(): void
    {
        [$owner, , $v] = $this->scene();
        $good = $this->codeFor($owner);
        $wrong = $good === '123456' ? '654321' : '123456';
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame('invalid', $this->reply("Y $wrong"));
        }
        $this->assertSame('rate_limited', $this->reply("Y $good"));                                // even the right code is refused for now
        $this->assertSame('proposed', $v->fresh()->status);
        $this->travel(11)->minutes();
        $this->assertSame('accepted', $this->reply("Y $good"));
    }

    public function test_an_sms_acceptance_cannot_complete_a_deal_the_investor_has_not_agreed_to(): void
    {
        [$owner, $inv, $v] = $this->scene();
        $v->forceFill(['investor_accepted_at' => null])->save();                                   // the investor has not accepted this version
        $this->assertSame('accepted', $this->reply('Y '.$this->codeFor($owner)));
        $this->assertSame(0, LoanAgreement::count(), 'one side agreeing never creates an agreement');
        $this->assertStringContainsString('waiting for the other side', SmsMessage::where('user_id', $owner->id)->where('kind', 'reply')->latest('id')->value('body'));
    }

    public function test_business_counter_offers_carry_no_reply_code_and_investors_cannot_answer_by_sms(): void
    {
        [$owner, $inv, $v, $c] = $this->scene();
        $counter = app(OfferService::class)->propose($c, $owner, ['principal' => 100000000, 'rate_bp' => 2000, 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'grace_instalments' => 0]);
        $text = SmsMessage::where('user_id', $inv->id)->where('kind', 'offer_received')->latest('id')->value('body');
        $this->assertStringNotContainsString('Reply Y', $text);
        $this->assertSame('unknown_sender', $this->reply('Y 123456', '0999123456'));
        $this->assertSame('proposed', $counter->fresh()->status);
    }

    public function test_the_stored_inbound_log_never_keeps_the_code(): void
    {
        [$owner] = $this->scene();
        $code = $this->codeFor($owner);
        $this->reply("Y $code");
        $this->assertStringNotContainsString($code, SmsInbound::first()->text_masked);
    }

    // ---- the gateway callback
    private function hook(array $payload, ?string $signature = 'auto')
    {
        $body = json_encode($payload);

        return $this->call('POST', '/webhooks/sms/inbound', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $signature === 'auto' ? SmsService::sign($body) : (string) $signature], $body);
    }

    public function test_the_gateway_callback_must_be_signed(): void
    {
        [$owner, , $v] = $this->scene();
        $p = ['event_id' => 'g-1', 'from' => '+265888555444', 'text' => 'Y '.$this->codeFor($owner)];
        $this->hook($p, null)->assertStatus(401);
        $this->hook($p, 'deadbeef')->assertStatus(401);
        $this->assertSame('proposed', $v->fresh()->status);
        $this->hook($p)->assertOk()->assertJson(['outcome' => 'accepted']);
        $this->hook($p)->assertOk()->assertJson(['outcome' => 'duplicate']);
        $this->assertSame(1, LoanAgreement::count());
    }

    public function test_malformed_callbacks_are_rejected(): void
    {
        $this->hook(['from' => '+265888555444', 'text' => 'Y 123456'])->assertStatus(422);
        $this->hook(['event_id' => 'x', 'from' => ['+265'], 'text' => 'Y 123456'])->assertStatus(422);
        $this->call('POST', '/webhooks/sms/inbound', [], [], [], ['HTTP_X_SIGNATURE' => SmsService::sign('not json')], 'not json')->assertStatus(422);
    }

    public function test_the_demo_phone_screen_replies_from_your_own_number_only(): void
    {
        [$owner, $inv, $v] = $this->scene();
        $code = $this->codeFor($owner);
        $this->actingAs($inv)->post('/sms-inbox/reply', ['text' => "Y $code"])->assertSessionHas('error');            // sent from the investor's number: refused
        $this->assertSame('proposed', $v->fresh()->status);
        $this->actingAs($owner)->post('/sms-inbox/reply', ['text' => "Y $code"])->assertSessionHas('success');
        $this->assertSame('accepted', $v->fresh()->status);
    }
}
