<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\LoanAgreement;
use App\Models\SmsActionToken;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\MessagingService;
use App\Services\OfferService;
use App\Services\Settings;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SmsTest extends TestCase
{
    private function phoned(Role $role, string $phone = '0999123456'): User
    {
        $u = $this->user($role);
        $role === Role::Investor ? $u->investorProfile->update(['phone' => $phone]) : $this->business($u, ['contact_phone' => $phone]);

        return $u->fresh();
    }

    private function code(User $u): string
    {
        preg_match('/code (\d{6})/', SmsMessage::where('user_id', $u->id)->where('kind', 'action_code')->latest('id')->value('body'), $m);

        return $m[1];
    }

    private function sms(): SmsService
    {
        return app(SmsService::class);
    }

    public function test_malawian_numbers_are_normalised_and_junk_is_rejected(): void
    {
        foreach (['0999123456', '0999 123 456', '+265 999 123 456', '265999123456', '999123456', '(+265) 999-123-456'] as $ok) {
            $this->assertSame('+265999123456', SmsService::normalise($ok), $ok);
        }
        foreach ([null, '', 'abc', '12345', '+44 7700 900123', '0999', '+2659991234567'] as $bad) {
            $this->assertNull(SmsService::normalise($bad), (string) $bad);
        }
    }

    public function test_messages_are_only_recorded_flagged_simulated_and_only_with_a_valid_phone(): void
    {
        $with = $this->phoned(Role::BusinessOwner);
        $without = $this->user(Role::BusinessOwner);
        $this->business($without, ['contact_phone' => 'not a number']);
        $this->assertNotNull($this->sms()->send($with, 'test', 'hello'));
        $this->assertNull($this->sms()->send($without->fresh(), 'test', 'hello'));
        $m = SmsMessage::first();
        $this->assertTrue($m->simulated);
        $this->assertSame(['mock', '+265999123456'], [$m->provider, $m->to_phone]);
        $with->forceFill(['status' => 'suspended'])->save();
        $this->assertNull($this->sms()->send($with->fresh(), 'test', 'again'));
    }

    public function test_dedupe_keys_make_a_message_send_once(): void
    {
        $u = $this->phoned(Role::BusinessOwner);
        $this->assertNotNull($this->sms()->send($u, 'r', 'x', 'k1'));
        $this->assertNull($this->sms()->send($u, 'r', 'x', 'k1'));
        $this->assertSame(1, SmsMessage::count());
    }

    public function test_a_code_is_six_digits_hashed_single_use_and_bound_to_user_action_and_record(): void
    {
        $u = $this->phoned(Role::Investor);
        $this->sms()->issueCode($u, 'accept_offer', 'offer_version', 7, 'accept the loan terms');
        $code = $this->code($u);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertStringNotContainsString($code, json_encode(SmsActionToken::first()->toArray()), 'the code is stored only as a keyed hash');

        // wrong user, wrong action, wrong record: all refused, and they do not burn the right user's code
        $other = $this->phoned(Role::Investor, '0888111222');
        foreach ([[$other, 'accept_offer', 7], [$u, 'other_action', 7], [$u, 'accept_offer', 8]] as [$who, $action, $id]) {
            try {
                $this->sms()->verify($who, $action, 'offer_version', $id, $code);
                $this->fail('should be bound');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->sms()->verify($u, 'accept_offer', 'offer_version', 7, $code);              // the right one works...
        $this->expectException(ValidationException::class);
        $this->sms()->verify($u, 'accept_offer', 'offer_version', 7, $code);              // ...once
    }

    public function test_codes_expire_and_wrong_guesses_lock_the_code(): void
    {
        $u = $this->phoned(Role::Investor);
        $this->sms()->issueCode($u, 'accept_offer', 'offer_version', 1, 'x');
        $code = $this->code($u);
        $this->travel(11)->minutes();
        try {
            $this->sms()->verify($u, 'accept_offer', 'offer_version', 1, $code);
            $this->fail('expired');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('expired', $e->errors()['sms_code'][0]);
        }
        $this->travelBack();

        $this->sms()->issueCode($u, 'accept_offer', 'offer_version', 2, 'x');
        $good = $this->code($u);
        $wrong = $good === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->sms()->verify($u, 'accept_offer', 'offer_version', 2, $wrong);
            } catch (ValidationException) {
            }
        }
        $this->expectException(ValidationException::class);
        $this->sms()->verify($u, 'accept_offer', 'offer_version', 2, $good);              // locked out even with the right code
    }

    public function test_requesting_a_new_code_cancels_the_old_one_and_issuing_is_rate_limited(): void
    {
        $u = $this->phoned(Role::Investor);
        $this->sms()->issueCode($u, 'accept_offer', 'offer_version', 3, 'x');
        $first = $this->code($u);
        $this->sms()->issueCode($u, 'accept_offer', 'offer_version', 3, 'x');
        $second = $this->code($u);
        if ($first !== $second) {
            try {
                $this->sms()->verify($u, 'accept_offer', 'offer_version', 3, $first);
                $this->fail('older code should be dead');
            } catch (ValidationException) {
            }
        }
        $this->sms()->issueCode($u, 'accept_offer', 'offer_version', 3, 'x');
        $this->expectException(ValidationException::class);
        $this->sms()->issueCode($u, 'accept_offer', 'offer_version', 3, 'x');             // fourth in ten minutes
    }

    public function test_the_audit_log_never_contains_a_code(): void
    {
        $u = $this->phoned(Role::Investor);
        $this->sms()->issueCode($u, 'accept_offer', 'offer_version', 1, 'x');
        $code = $this->code($u);
        $this->sms()->verify($u, 'accept_offer', 'offer_version', 1, $code);
        $this->assertStringNotContainsString($code, json_encode(DB::table('audit_logs')->get()));
        $this->assertDatabaseHas('audit_logs', ['action' => 'sms.code_confirmed']);
    }

    // ---- step-up on accepting an offer
    private function offerFor(User $investor): array
    {
        $listing = $this->listing();
        $owner = $listing->application->business->owner;
        $owner->business->update(['contact_phone' => '0888555444']);
        $c = app(MessagingService::class)->start($listing, $investor);
        $v = app(OfferService::class)->propose($c, $investor, ['principal' => 100000000, 'rate_bp' => 2200, 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'grace_instalments' => 0]);

        return [$c, $v, $owner->fresh()];
    }

    public function test_when_required_accepting_an_offer_needs_the_code_sent_to_that_users_phone(): void
    {
        Settings::set('sms.confirm_acceptance', true);
        $inv = $this->phoned(Role::Investor);
        [$c, $v, $owner] = $this->offerFor($inv);

        $this->actingAs($owner)->post("/messages/{$c->id}/offers/{$v->id}/accept")->assertSessionHasErrors('sms_code');
        $this->actingAs($owner)->post("/messages/{$c->id}/offers/{$v->id}/accept", ['sms_code' => '123456'])->assertSessionHasErrors('sms_code');
        $this->assertSame(0, LoanAgreement::count());

        $this->actingAs($owner)->post("/messages/{$c->id}/offers/{$v->id}/sms-code")->assertSessionHasNoErrors();
        $code = $this->code($owner);
        $this->actingAs($inv)->post("/messages/{$c->id}/offers/{$v->id}/accept", ['sms_code' => $code])->assertSessionHasErrors();      // someone else's code is useless
        $this->actingAs($owner)->post("/messages/{$c->id}/offers/{$v->id}/accept", ['sms_code' => $code])->assertSessionHasNoErrors();
        $this->assertSame(1, LoanAgreement::count());
    }

    public function test_the_code_does_not_replace_the_other_partys_acceptance_or_any_approval(): void
    {
        Settings::set('sms.confirm_acceptance', true);
        $inv = $this->phoned(Role::Investor);
        [$c, $v, $owner] = $this->offerFor($inv);
        $v->forceFill(['investor_accepted_at' => null])->save();                                  // the investor has NOT accepted
        $this->actingAs($owner)->post("/messages/{$c->id}/offers/{$v->id}/sms-code");
        $this->actingAs($owner)->post("/messages/{$c->id}/offers/{$v->id}/accept", ['sms_code' => $this->code($owner)])->assertSessionHasNoErrors();
        $this->assertSame(0, LoanAgreement::count(), 'one side confirming by SMS never completes an agreement');
        $this->assertNotNull($v->fresh()->business_accepted_at);
    }

    public function test_without_a_phone_or_with_the_setting_off_the_web_flow_still_works(): void
    {
        $inv = $this->phoned(Role::Investor);
        [$c, $v, $owner] = $this->offerFor($inv);
        $this->actingAs($owner)->post("/messages/{$c->id}/offers/{$v->id}/accept")->assertSessionHasNoErrors();           // setting off
        $this->assertSame(1, LoanAgreement::count());

        Settings::set('sms.confirm_acceptance', true);
        $inv2 = $this->user(Role::Investor);                                                                                  // no phone on file
        $this->assertFalse($this->sms()->requiresConfirmation($inv2));
    }

    public function test_sms_codes_cannot_be_requested_for_offers_you_are_not_part_of(): void
    {
        Settings::set('sms.confirm_acceptance', true);
        [$c, $v] = $this->offerFor($this->phoned(Role::Investor));
        $this->actingAs($this->phoned(Role::Investor, '0777000111'))->post("/messages/{$c->id}/offers/{$v->id}/sms-code")->assertNotFound();
        $this->assertSame(0, SmsActionToken::where('action', 'accept_offer')->count());
    }

    // ---- notifications, reminders, views
    public function test_status_changes_and_offers_text_the_owner_and_never_leak_sensitive_data(): void
    {
        $inv = $this->phoned(Role::Investor);
        [$c, $v, $owner] = $this->offerFor($inv);
        $texts = SmsMessage::where('user_id', $owner->id)->pluck('body')->implode(' | ');
        $this->assertStringContainsString('an investor offers MK 1,000,000 at 22% a year over 12 months', $texts, 'the plan: amount, rate and period are in the SMS');
        foreach ([$inv->name, $inv->email, '0999123456', '+265999123456'] as $secret) {
            $this->assertStringNotContainsString($secret, $texts, 'no names or contact details are texted');
        }
        $this->actingAs($owner)->post("/messages/{$c->id}/offers/{$v->id}/accept");
        $this->assertSame(1, SmsMessage::where('user_id', $inv->id)->where('kind', 'agreement_created')->count());
    }

    public function test_repayment_reminders_go_out_once_per_instalment(): void
    {
        $a = $this->activeLoan(60000000);
        $a->business->update(['contact_phone' => '0999777666']);
        $a->schedule()->orderBy('number')->first()->update(['due_on' => now()->addDays(3)->toDateString()]);
        $this->artisan('banyira:send-reminders')->assertSuccessful();
        $this->artisan('banyira:send-reminders')->assertSuccessful();
        $this->assertSame(1, SmsMessage::where('kind', 'repayment_reminder')->count());
        $a->schedule()->orderBy('number')->first()->update(['due_on' => now()->subDay()->toDateString()]);
        $this->artisan('banyira:send-reminders')->assertSuccessful();
        $this->assertSame(2, SmsMessage::where('kind', 'repayment_reminder')->count());     // a separate "missed" reminder
    }

    public function test_inbox_shows_only_your_own_messages_and_staff_log_masks_codes(): void
    {
        $u = $this->phoned(Role::Investor);
        $other = $this->phoned(Role::Investor, '0888000999');
        $this->sms()->send($u, 'note', 'Hello investor');
        $this->sms()->send($other, 'note', 'Private to someone else');
        $this->actingAs($u)->get('/sms-inbox')->assertInertia(fn ($p) => $p->has('messages', 1)->where('messages.0.body', 'Hello investor'));
        $this->sms()->issueCode($u, 'accept_offer', 'offer_version', 1, 'x');
        $code = $this->code($u);
        $res = $this->actingAs($this->user(Role::ItAdmin))->get('/sms-log')->assertOk();
        $this->assertStringNotContainsString($code, $res->getContent());
        $this->actingAs($this->user(Role::Support))->get('/sms-log')->assertOk();
        $this->actingAs($this->user(Role::Investor))->get('/sms-log')->assertForbidden();
        $this->actingAs($this->user(Role::Finance))->get('/sms-log')->assertForbidden();
    }

    public function test_sms_can_never_authorise_anything_by_reply(): void
    {
        foreach (['/webhooks/sms', '/webhooks/sms/mock', '/sms/reply'] as $url) {
            $this->post($url, ['from' => '+265999123456', 'text' => 'YES 123456'])->assertNotFound();
        }
    }
}
