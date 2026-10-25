<?php

namespace App\Support;

/** Amounts are integer minor units (tambala). Rates are basis points. bcmath is used wherever a fraction appears. */
final class Money
{
    public const CURRENCY = 'MWK';

    public const DECIMAL_RULE = 'regex:/^\d{1,12}(\.\d{1,2})?$/';

    public static function toMinor(string|int|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        [$whole, $frac] = array_pad(explode('.', str_replace(',', '', (string) $value), 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($frac, 0, 2), 2, '0');
    }

    public static function format(int $minor): string
    {
        return 'MK '.number_format($minor / 100, $minor % 100 === 0 ? 0 : 2);
    }

    /** Round half up (away from zero) a bcmath decimal string to an integer. */
    public static function round(string $value): int
    {
        $neg = str_starts_with($value, '-');
        $abs = ltrim($value, '-');
        $r = bcadd($abs, '0.5', 0);

        return (int) ($neg ? '-'.$r : $r);
    }

    /** round(amount * bp / 10000), no floats. */
    public static function bp(int $amount, int $bp): int
    {
        return self::round(bcdiv(bcmul((string) $amount, (string) $bp, 0), '10000', 6));
    }
}
