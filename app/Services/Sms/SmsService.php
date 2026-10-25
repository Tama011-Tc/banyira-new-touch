<?php

namespace App\Services\Sms;

use App\Models\SmsActionToken;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Settings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Notifications and one-time confirmation codes.
 *
 * Rules that keep SMS from becoming a back door:
 *  - A code is 6 digits, valid 10 minutes, single use, bound to ONE user, ONE action and ONE record, stored only as a keyed hash,
 *    limited to 5 attempts, and limited to 3 issues per user per 10 minutes.
 *  - A code can confirm an action the user is already allowed to take on the web. It never grants a permission and never
 *    bypasses credit approval or agreement acceptance.
 *  - Replies by SMS are not accepted at all: the user types the code into the website.
 *  - The website works fully without SMS.
 */
class SmsService
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const MAX_ISSUES = 3;

    public function __construct(private readonly SmsProvider $provider, private readonly AuditLogger $audit) {}

    /** +265 followed by 9 digits, or null. Accepts 0999 123 456, 265999123456, +265 999 123 456. */
    public static function normalise(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        if ($digits === '') {
            return null;
        }
        $e164 = match (true) {
            str_starts_with($digits, '265') && strlen($digits) === 12 => '+'.$digits,
            str_starts_with($digits, '0') && strlen($digits) === 10 => '+265'.substr($digits, 1),
            strlen($digits) === 9 => '+265'.$digits,
            default => null,
        };

        return $e164 && preg_match('/^\+265\d{9}$/', $e164) ? $e164 : null;
    }

    public function phoneFor(User $user): ?string
    {
        $raw = match ($user->role->value) {
            'business_owner' => $user->business?->contact_phone,
            'investor' => $user->investorProfile?->phone,
            default => null,
        };

        return self::normalise($raw);
    }

    /** Sends (records) a message if the user has a valid phone. Never throws: SMS must not break the web workflow. */
    public function send(User $user, string $kind, string $body, ?string $dedupeKey = null): ?SmsMessage
    {
        $phone = $this->phoneFor($user);
        if (! $phone || $user->status !== 'active') {
            return null;
        }
        try {
            $r = $this->provider->send($phone, $body);

            return SmsMessage::create(['user_id' => $user->id, 'to_phone' => $phone, 'kind' => $kind, 'body' => $body, 'provider' => $this->provider->name(), 'simulated' => $this->provider->isSimulated(), 'status' => $r['status'], 'provider_ref' => $r['ref'], 'dedupe_key' => $dedupeKey, 'created_at' => now()]);
        } catch (UniqueConstraintViolationException) {
            return null;   // already sent (idempotent reminder)
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    public function requiresConfirmation(User $user): bool
    {
        return (bool) Settings::get('sms.confirm_acceptance') && $this->phoneFor($user) !== null;
    }

    public function hashFor(User $user, string $action, string $type, int $id, string $code): string
    {
        return $this->hash($user, $action, $type, $id, $code);
    }

    public static function sign(string $body): string
    {
        return hash_hmac('sha256', $body, config('banyira.sms_webhook_secret') ?: hash('sha256', 'banyira-sms|'.config('app.key')));
    }

    public static function validSignature(string $body, ?string $signature): bool
    {
        return $signature !== null && hash_equals(self::sign($body), $signature);
    }

    private function hash(User $user, string $action, string $type, int $id, string $code): string
    {
        return hash_hmac('sha256', "{$user->id}|{$action}|{$type}|{$id}|{$code}", (string) config('app.key'));
    }

    public function issueCode(User $user, string $action, string $subjectType, int $subjectId, string $what): void
    {
        $recent = SmsActionToken::where('user_id', $user->id)->where('created_at', '>', now()->subMinutes(self::TTL_MINUTES))->count();
        if ($recent >= self::MAX_ISSUES) {
            throw ValidationException::withMessages(['sms_code' => 'Too many codes requested. Wait a few minutes and try again.']);
        }
        if (! $this->phoneFor($user)) {
            throw ValidationException::withMessages(['sms_code' => 'There is no valid mobile number on your profile.']);
        }
        SmsActionToken::where('user_id', $user->id)->where('action', $action)->where('subject_type', $subjectType)->where('subject_id', $subjectId)->whereNull('used_at')->update(['used_at' => now()]);   // older codes stop working
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        SmsActionToken::create(['user_id' => $user->id, 'action' => $action, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'code_hash' => $this->hash($user, $action, $subjectType, $subjectId, $code), 'expires_at' => now()->addMinutes(self::TTL_MINUTES), 'ip' => request()?->ip(), 'created_at' => now()]);
        $brand = config('banyira.brand');
        $this->send($user, 'action_code', "{$brand}: code {$code} to {$what}. Expires in ".self::TTL_MINUTES." minutes. Never share it; staff will never ask for it.");
        $this->audit->record('sms.code_issued', null, ['action' => $action, 'subject' => "{$subjectType}#{$subjectId}"], $user);
    }

    /** Throws unless the code is right, fresh, unused, and was issued to THIS user for THIS action on THIS record. */
    public function verify(User $user, string $action, string $subjectType, int $subjectId, ?string $code): void
    {
        $fail = fn (string $why) => throw ValidationException::withMessages(['sms_code' => $why]);
        $code = preg_replace('/\D/', '', (string) $code);
        $token = SmsActionToken::where('user_id', $user->id)->where('action', $action)->where('subject_type', $subjectType)->where('subject_id', $subjectId)->whereNull('used_at')->latest('id')->first();
        if (! $token || strlen($code) !== 6) {
            $fail('Enter the 6-digit code we sent to your phone. Request a new one if needed.');
        }
        if ($token->expires_at->isPast()) {
            $token->forceFill(['used_at' => now()])->save();
            $fail('That code has expired. Request a new one.');
        }
        if ($token->attempts >= self::MAX_ATTEMPTS) {
            $token->forceFill(['used_at' => now()])->save();
            $fail('Too many wrong attempts. Request a new code.');
        }
        $token->increment('attempts');
        if (! hash_equals($token->code_hash, $this->hash($user, $action, $subjectType, $subjectId, $code))) {
            $this->audit->record('sms.code_failed', null, ['action' => $action, 'subject' => "{$subjectType}#{$subjectId}"], $user);
            $fail('That code is not right.');
        }
        $token->forceFill(['used_at' => now()])->save();   // single use
        $this->audit->record('sms.code_confirmed', null, ['action' => $action, 'subject' => "{$subjectType}#{$subjectId}"], $user);
    }
}
