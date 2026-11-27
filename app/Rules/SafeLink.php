<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A link typed by an editor: a page on this site, an https address, an email
 * address or a phone number. Script URLs and the staff portal are refused.
 */
class SafeLink implements ValidationRule
{
    private const PATTERN = '~^(/(?!/)[^\s\\\\]*|\#[A-Za-z0-9_-]+|https://[^\s\\\\]+|mailto:[^\s\\\\]+@[^\s\\\\]+|tel:\+?[0-9-]+)$~';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value)) {
            $fail('Use a page on this site (starting with /), an https:// address, mailto: or tel:.');

            return;
        }
        if (preg_match('~(^|/)internal(-access)?(/|\?|\#|$)~i', rawurldecode($value))) {
            $fail('The public website cannot link to the staff portal.');
        }
    }
}
