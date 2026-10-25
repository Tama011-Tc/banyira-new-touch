<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Decimal-safe loan maths (bcmath, integer minor units, round half up, last instalment absorbs residue).
 *
 *  flat:    interest = principal x annual rate x (term months / 12), spread evenly; principal spread evenly over
 *           the non-grace instalments.
 *  reducing: level payment on the declining balance; interest each period = round(balance x periodic rate).
 *  grace:   the first N instalments are interest-only.
 */
final class LoanCalculator
{
    public const FLAT = 'flat';

    public const REDUCING = 'reducing';

    public static function periodsPerYear(string $frequency): int
    {
        return $frequency === 'weekly' ? 52 : 12;
    }

    public static function instalments(int $termMonths, string $frequency): int
    {
        return $frequency === 'weekly' ? max(1, intdiv($termMonths * 52, 12)) : $termMonths;
    }

    /**
     * @return array{instalments: list<array{number:int, due_on:string, principal:int, interest:int, total:int, balance_after:int}>, total_principal:int, total_interest:int, total_payable:int}
     */
    public static function schedule(int $principal, int $rateBp, int $termMonths, string $frequency, string $method, int $graceInstalments, string $startDate): array
    {
        if ($principal <= 0 || $termMonths <= 0 || $rateBp < 0) {
            throw new \InvalidArgumentException('Principal and term must be positive and the rate cannot be negative.');
        }
        if (! in_array($method, [self::FLAT, self::REDUCING], true) || ! in_array($frequency, ['weekly', 'monthly'], true)) {
            throw new \InvalidArgumentException('Unsupported interest method or repayment frequency.');
        }
        $n = self::instalments($termMonths, $frequency);
        $g = max(0, min($graceInstalments, $n - 1));
        $amortising = $n - $g;
        $start = CarbonImmutable::parse($startDate);
        $rows = [];

        if ($method === self::FLAT) {
            $totalInterest = Money::round(bcdiv(bcmul(bcmul((string) $principal, (string) $rateBp, 0), (string) $termMonths, 0), '120000', 6));
            $balance = $principal;
            for ($i = 1; $i <= $n; $i++) {
                $last = $i === $n;
                $interest = $last ? $totalInterest - array_sum(array_column($rows, 'interest')) : intdiv($totalInterest, $n);
                $princ = $i <= $g ? 0 : ($last ? $balance : intdiv($principal, $amortising));
                $balance -= $princ;
                $rows[] = self::row($i, $start, $frequency, $princ, $interest, $balance);
            }
        } else {
            $r = bcdiv(bcdiv((string) $rateBp, '10000', 12), (string) self::periodsPerYear($frequency), 12);
            $balance = $principal;
            $payment = self::levelPayment($principal, $r, $amortising);
            for ($i = 1; $i <= $n; $i++) {
                $interest = Money::round(bcmul((string) $balance, $r, 6));
                if ($i <= $g) {
                    $princ = 0;
                } elseif ($i === $n) {
                    $princ = $balance;
                } else {
                    $princ = min($balance, max(0, $payment - $interest));
                }
                $balance -= $princ;
                $rows[] = self::row($i, $start, $frequency, $princ, $interest, $balance);
            }
        }

        $ti = array_sum(array_column($rows, 'interest'));

        return ['instalments' => $rows, 'total_principal' => $principal, 'total_interest' => $ti, 'total_payable' => $principal + $ti];
    }

    private static function levelPayment(int $principal, string $r, int $n): int
    {
        if (bccomp($r, '0', 12) === 0) {
            return Money::round(bcdiv((string) $principal, (string) $n, 6));
        }
        $pow = bcpow(bcadd('1', $r, 12), (string) $n, 12);   // (1+r)^n
        $numerator = bcmul(bcmul((string) $principal, $r, 12), $pow, 12);
        $denominator = bcsub($pow, '1', 12);

        return Money::round(bcdiv($numerator, $denominator, 6));
    }

    private static function row(int $i, CarbonImmutable $start, string $frequency, int $principal, int $interest, int $balance): array
    {
        $due = $frequency === 'weekly' ? $start->addWeeks($i) : $start->addMonthsNoOverflow($i);

        return ['number' => $i, 'due_on' => $due->toDateString(), 'principal' => $principal, 'interest' => $interest, 'total' => $principal + $interest, 'balance_after' => $balance];
    }
}
