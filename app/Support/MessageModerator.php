<?php

namespace App\Support;

/**
 * Server-side screening of marketplace messages for contact details and off-platform payment attempts.
 * Heuristic, not perfect: determined users can obfuscate, so reporting, rate limits and human moderation back it up.
 * Rules can be switched off or tuned through the `moderation.rules` setting.
 */
final class MessageModerator
{
    public const BLOCK = 'block';

    public const REVIEW = 'review';

    public const ALLOW = 'allow';

    /** @return array{action:string, matches:list<array{rule:string,excerpt:string}>} */
    public static function inspect(string $body, ?array $enabled = null): array
    {
        $enabled ??= array_keys(self::rules());
        $matches = [];
        foreach (self::rules() as $name => [$action, $detector]) {
            if (in_array($name, $enabled, true) && ($excerpt = $detector($body)) !== null) {
                $matches[] = ['rule' => $name, 'excerpt' => mb_substr($excerpt, 0, 40), 'action' => $action];
            }
        }
        $action = self::ALLOW;
        foreach ($matches as $m) {
            if ($m['action'] === self::BLOCK) {
                $action = self::BLOCK;
                break;
            }
            $action = self::REVIEW;
        }

        return ['action' => $action, 'matches' => array_map(fn ($m) => ['rule' => $m['rule'], 'excerpt' => $m['excerpt']], $matches)];
    }

    /** @return array<string, array{0:string, 1:callable(string):?string}> */
    public static function rules(): array
    {
        return [
            'email' => [self::BLOCK, fn ($t) => self::first('/[A-Z0-9._%+\-]+\s?(?:@|\(at\)|\[at\])\s?[A-Z0-9.\-]+\s?(?:\.|\(dot\)|\[dot\])\s?[A-Z]{2,}/i', $t)],
            'url' => [self::BLOCK, fn ($t) => self::first('~(?:https?://|www\.|\b[a-z0-9\-]+\.(?:com|net|org|mw|co|io|me|info|biz)\b)~i', $t)],
            'phone' => [self::BLOCK, fn ($t) => self::phone($t)],
            'messaging_app' => [self::BLOCK, fn ($t) => self::first('/\b(?:whats\s?app|wa\.me|telegram|signal|viber|messenger|imo|wechat|facebook|insta(?:gram)?|tiktok|snapchat|twitter)\b/i', $t)],
            'handle' => [self::BLOCK, fn ($t) => self::first('/(?<![\w.])@[a-z0-9_.]{3,}/i', $t)],
            'payment_instruction' => [self::BLOCK, fn ($t) => self::first('/\b(?:airtel\s?money|tnm\s?mpamba|mpamba|mo626|bank\s+account|account\s+(?:number|no\.?)|acc\.?\s?(?:no|number)|send\s+(?:it\s+)?to\s+my|pay\s+me\s+directly|iban|swift)\b/i', $t)],
            'off_platform' => [self::REVIEW, fn ($t) => self::first('/\b(?:outside\s+(?:the\s+)?(?:platform|app|site)|off[\s\-]?platform|call\s+me|phone\s+me|text\s+me|meet\s+(?:me\s+)?in\s+person|lets?\s+talk\s+(?:privately|directly)|contact\s+me\s+(?:directly|on))\b/i', $t)],
        ];
    }

    private static function first(string $pattern, string $text): ?string
    {
        return preg_match($pattern, $text, $m) ? $m[0] : null;
    }

    /** 9+ digits once separators are stripped, or a +265 / 0[89]... Malawian pattern. Amounts like "MK 500,000" are skipped. */
    private static function phone(string $text): ?string
    {
        // Remove amounts so "MK 1,500,000" or "500,000 kwacha" never look like numbers.
        $clean = preg_replace('/(?:MK|MWK)\s?\d[\d,\s.]*|\b\d{1,3}(?:,\d{3})+(?:\.\d+)?\b/i', ' ', $text) ?? $text;
        if (preg_match('/(?:\+|00)?265[\s\-.]?\(?\d[\d\s\-.()]{6,}/', $clean, $m)) {
            return $m[0];
        }
        if (preg_match('/\b0[\s\-.]?[1-9][\d\s\-.]{7,}\d/', $clean, $m)) {
            return $m[0];
        }
        if (preg_match_all('/\+?\d[\d\s\-.()]{7,}\d/', $clean, $all)) {
            foreach ($all[0] as $candidate) {
                if (strlen(preg_replace('/\D/', '', $candidate)) >= 9) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
