<?php

namespace App\Services\Sms;

use App\Models\Business;
use App\Models\OfferVersion;
use App\Models\SmsActionToken;
use App\Models\SmsInbound;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OfferService;
use App\Services\Settings;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * The business plan lets an owner without internet accept or decline an investor's offer by SMS: "Y 123456" / "N 123456".
 *
 * A reply is treated as an instruction ONLY when ALL of these hold:
 *  1. the gateway's signed callback is authentic (checked by the webhook controller);
 *  2. the sender's number is the business owner's registered number;
 *  3. the 6-digit code matches a reply code we issued to THAT owner for a specific, still-open offer version;
 *  4. the code is unused and unexpired (single use, 48 hours by default);
 *  5. the offer is the latest version, still proposed, not expired, and the sender is the owner of the business it concerns;
 *  6. the amount is within the SMS limit (larger offers must be accepted on the website).
 * It then performs exactly the action the owner could perform on the website, through the same OfferService rules
 * (listing open, capacity, limits). It never bypasses credit approval, the investor's side of the agreement, or any check.
 * Failed attempts per number are rate limited. Anything else gets a generic reply and changes nothing.
 */
class SmsInboundService
{
    public const ACTION = 'sms_reply_offer';

    public function __construct(private readonly SmsService $sms, private readonly OfferService $offers, private readonly AuditLogger $audit) {}

    /** Issue the reply code for an offer and return it for inclusion in the outbound SMS. */
    public function issueReplyCode(User $owner, OfferVersion $v): string
    {
        SmsActionToken::where('user_id', $owner->id)->where('action', self::ACTION)->where('subject_id', $v->id)->whereNull('used_at')->update(['used_at' => now()]);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        SmsActionToken::create(['user_id' => $owner->id, 'action' => self::ACTION, 'subject_type' => 'offer_version', 'subject_id' => $v->id,
            'code_hash' => $this->sms->hashFor($owner, self::ACTION, 'offer_version', $v->id, $code), 'expires_at' => now()->addHours((int) Settings::get('sms.reply_ttl_hours')), 'created_at' => now()]);

        return $code;
    }

    /** @return string the outcome (also stored); a reply SMS is sent where useful */
    public function handle(string $eventId, string $fromRaw, string $text): string
    {
        $from = SmsService::normalise($fromRaw) ?? 'invalid';
        $masked = preg_replace('/\d{6}/', '••••••', mb_substr($text, 0, 60));
        $record = function (string $outcome, ?User $user = null) use ($eventId, $from, $masked) {
            try {
                SmsInbound::create(['event_id' => $eventId, 'from_phone' => $from, 'user_id' => $user?->id, 'text_masked' => $masked, 'outcome' => $outcome, 'created_at' => now()]);
            } catch (UniqueConstraintViolationException) {
                return 'duplicate';
            }

            return $outcome;
        };

        if (SmsInbound::where('event_id', $eventId)->exists()) {
            return 'duplicate';
        }
        $bad = SmsInbound::where('from_phone', $from)->whereIn('outcome', ['invalid', 'not_understood', 'unknown_sender'])->where('created_at', '>', now()->subMinutes(10))->count();
        if ($bad >= 5) {
            return $record('rate_limited');
        }
        $owner = $this->ownerByPhone($from);
        if (! $owner) {
            return $record('unknown_sender');          // no reply: do not confirm anything to a stranger
        }
        if (! preg_match('/^\s*(Y|YES|ACCEPT|N|NO|DECLINE|REJECT)\s*(\d{6})\s*$/i', $text, $m)) {
            $this->reply($owner, 'Not understood. To answer an offer reply Y or N followed by your 6-digit code, for example: Y 123456.');

            return $record('not_understood', $owner);
        }
        $accept = in_array(strtoupper($m[1]), ['Y', 'YES', 'ACCEPT'], true);

        $token = SmsActionToken::where('user_id', $owner->id)->where('action', self::ACTION)->whereNull('used_at')->where('expires_at', '>', now())->get()
            ->first(fn ($t) => hash_equals($t->code_hash, $this->sms->hashFor($owner, self::ACTION, 'offer_version', $t->subject_id, $m[2])));
        if (! $token) {
            $this->reply($owner, 'That code is not valid or has expired. Open the app to answer this offer.');

            return $record('invalid', $owner);
        }
        $v = OfferVersion::with('offer.conversation.business', 'offer.conversation.listing')->find($token->subject_id);
        $c = $v?->offer->conversation;
        if (! $v || $c->business->owner_id !== $owner->id || $v->status !== 'proposed' || $v->id !== (int) $v->offer->versions()->max('id')) {
            $token->forceFill(['used_at' => now()])->save();
            $this->reply($owner, 'This offer is no longer open. Open the app to see its current state.');

            return $record('invalid', $owner);
        }
        $token->forceFill(['used_at' => now()])->save();   // single use, whatever happens next

        try {
            if (! $accept) {
                $this->offers->decline($v, $owner);
                $this->audit->record('sms.reply_declined', $v, ['channel' => 'sms'], $owner);
                $this->reply($owner, 'Offer declined. Nothing further is needed.');

                return $record('declined', $owner);
            }
            if ($v->principal > (int) Settings::get('sms.max_accept_amount')) {
                $this->reply($owner, 'This offer is too large to accept by SMS. Please log in to review and accept it.');

                return $record('needs_web', $owner);
            }
            $agreement = $this->offers->accept($v, $owner, null, viaSms: true);
            $this->audit->record('sms.reply_accepted', $v, ['channel' => 'sms', 'agreement' => $agreement?->id], $owner);
            $this->reply($owner, $agreement ? 'Offer accepted. Both sides agreed: open the app to see your agreement and repayment schedule.' : 'Your acceptance is recorded. We are waiting for the other side.');

            return $record('accepted', $owner);
        } catch (ValidationException|\Symfony\Component\HttpKernel\Exception\HttpException) {
            $this->reply($owner, 'We could not complete that. Please open the app to check the offer.');

            return $record('invalid', $owner);
        }
    }

    private function reply(User $owner, string $body): void
    {
        $this->sms->send($owner, 'reply', config('banyira.brand').': '.$body);
    }

    /** The business owner whose registered number this is; null if none or if it is ambiguous. */
    private function ownerByPhone(string $e164): ?User
    {
        if ($e164 === 'invalid') {
            return null;
        }
        $tail = substr($e164, -9);
        $matches = Business::where('contact_phone', 'like', '%'.$tail.'%')->get()->filter(fn ($b) => SmsService::normalise($b->contact_phone) === $e164)->pluck('owner_id')->unique();

        return $matches->count() === 1 ? User::where('id', $matches->first())->where('status', 'active')->first() : null;
    }
}
