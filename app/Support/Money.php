<?php

namespace App\Support;

/**
 * Decimal money arithmetic on numeric strings (bcmath). Never use floats for
 * amounts. Every public method returns a string with two decimal places.
 */
final class Money
{
    private const WORK = 12;

    public static function of(string|int|null $v): string
    {
        return self::round((string) ($v ?? '0'));
    }

    public static function add(string ...$v): string
    {
        $t = '0';
        foreach ($v as $x) {
            $t = bcadd($t, $x, self::WORK);
        }

        return self::round($t);
    }

    public static function sub(string $a, string $b): string
    {
        return self::round(bcsub($a, $b, self::WORK));
    }

    public static function mul(string $a, string $b): string
    {
        return self::round(bcmul($a, $b, self::WORK));
    }

    /** Percentage of an amount, e.g. pct('1000000', '10') = '100000.00'. */
    public static function pct(string $amount, string $rate): string
    {
        return self::round(bcdiv(bcmul($amount, $rate, self::WORK), '100', self::WORK));
    }

    public static function cmp(string $a, string $b): int
    {
        return bccomp($a, $b, 2);
    }

    public static function min(string $a, string $b): string
    {
        return self::cmp($a, $b) <= 0 ? self::of($a) : self::of($b);
    }

    public static function isZero(string $a): bool
    {
        return self::cmp($a, '0') === 0;
    }

    /** Round half away from zero to the given scale. */
    public static function round(string $v, int $scale = 2): string
    {
        $offset = '0.'.str_repeat('0', $scale).'5';
        $v = str_starts_with($v, '-') ? bcsub($v, $offset, $scale + 1) : bcadd($v, $offset, $scale + 1);

        return bcadd($v, '0', $scale);
    }

    public static function format(string $v): string
    {
        return 'MK'.number_format((float) $v, 2);
    }

    /**
     * Level-payment amortisation. Returns one row per month with principal and
     * interest rounded to the tambala; the final row clears any rounding drift.
     *
     * @return list<array{no:int, principal:string, interest:string}>
     */
    public static function amortise(string $principal, string $annualRate, int $months): array
    {
        $r = bcdiv($annualRate, '1200', self::WORK);
        if (bccomp($r, '0', self::WORK) === 0) {
            $payment = self::round(bcdiv($principal, (string) $months, self::WORK));
        } else {
            $g = bcpow(bcadd('1', $r, self::WORK), (string) $months, self::WORK);
            $payment = self::round(bcdiv(bcmul(bcmul($principal, $r, self::WORK), $g, self::WORK), bcsub($g, '1', self::WORK), self::WORK));
        }

        $rows = [];
        $balance = self::of($principal);
        for ($i = 1; $i <= $months; $i++) {
            $interest = self::round(bcmul($balance, $r, self::WORK));
            $part = $i === $months ? $balance : self::sub($payment, $interest);
            if (self::cmp($part, $balance) > 0) {
                $part = $balance;
            }
            $rows[] = ['no' => $i, 'principal' => $part, 'interest' => $interest];
            $balance = self::sub($balance, $part);
        }

        return $rows;
    }
}
