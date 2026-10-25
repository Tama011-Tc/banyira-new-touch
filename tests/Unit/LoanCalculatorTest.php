<?php

namespace Tests\Unit;

use App\Support\LoanCalculator as L;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

class LoanCalculatorTest extends TestCase
{
    public function test_flat_interest_is_on_the_original_principal(): void
    {
        $s = L::schedule(12000000, 2400, 12, 'monthly', 'flat', 0, '2026-01-31');   // MK120,000 at 24% flat for a year
        $this->assertSame(2880000, $s['total_interest']);                            // MK28,800
        $this->assertSame(14880000, $s['total_payable']);
        $this->assertCount(12, $s['instalments']);
        $this->assertSame(1240000, $s['instalments'][0]['total']);                   // MK12,400 each
        $this->assertSame(0, end($s['instalments'])['balance_after']);
    }

    public function test_reducing_balance_amortises_to_exactly_zero(): void
    {
        $s = L::schedule(12000000, 2400, 12, 'monthly', 'reducing', 0, '2026-01-01');
        $this->assertSame(12000000, array_sum(array_column($s['instalments'], 'principal')));
        $this->assertSame(0, end($s['instalments'])['balance_after']);
        $this->assertSame(240000, $s['instalments'][0]['interest']);                  // 2% of MK120,000
        $this->assertSame(1134715, $s["instalments"][0]["total"]);       // standard annuity payment
        $this->assertLessThan(2880000, $s['total_interest']);                          // cheaper than flat
        $this->assertSame($s['total_principal'] + $s['total_interest'], $s['total_payable']);
    }

    public function test_last_instalment_absorbs_rounding(): void
    {
        $s = L::schedule(1000001, 1850, 7, 'monthly', 'flat', 0, '2026-01-01');
        $this->assertSame(1000001, array_sum(array_column($s['instalments'], 'principal')));
        $this->assertSame($s['total_interest'], array_sum(array_column($s['instalments'], 'interest')));
    }

    public function test_zero_rate_splits_principal_evenly(): void
    {
        $s = L::schedule(1200000, 0, 12, 'monthly', 'reducing', 0, '2026-01-01');
        $this->assertSame(0, $s['total_interest']);
        $this->assertSame(100000, $s['instalments'][5]['principal']);
    }

    public function test_grace_instalments_are_interest_only(): void
    {
        foreach (['flat', 'reducing'] as $method) {
            $s = L::schedule(12000000, 2400, 12, 'monthly', $method, 2, '2026-01-01');
            $this->assertSame(0, $s['instalments'][0]['principal'], $method);
            $this->assertSame(0, $s['instalments'][1]['principal'], $method);
            $this->assertGreaterThan(0, $s['instalments'][2]['principal'], $method);
            $this->assertSame(12000000, array_sum(array_column($s['instalments'], 'principal')), $method);
            $this->assertSame(0, end($s['instalments'])['balance_after'], $method);
        }
    }

    public function test_weekly_schedules_and_month_end_dates(): void
    {
        $w = L::schedule(5200000, 2000, 6, 'weekly', 'reducing', 0, '2026-01-01');
        $this->assertCount(26, $w['instalments']);
        $this->assertSame('2026-01-08', $w['instalments'][0]['due_on']);
        $m = L::schedule(1000000, 1000, 3, 'monthly', 'flat', 0, '2026-01-31');
        $this->assertSame(['2026-02-28', '2026-03-31', '2026-04-30'], array_column($m['instalments'], 'due_on'));
    }

    public function test_no_floating_point_drift_on_large_amounts(): void
    {
        $s = L::schedule(99999999999, 3333, 36, 'monthly', 'reducing', 0, '2026-01-01');
        $this->assertSame(99999999999, array_sum(array_column($s['instalments'], 'principal')));
        $this->assertSame(0, end($s['instalments'])['balance_after']);
    }

    public function test_invalid_inputs_are_rejected(): void
    {
        foreach ([[0, 100, 12, 'monthly', 'flat'], [100, -1, 12, 'monthly', 'flat'], [100, 100, 0, 'monthly', 'flat'], [100, 100, 12, 'daily', 'flat'], [100, 100, 12, 'monthly', 'compound']] as $args) {
            try {
                L::schedule($args[0], $args[1], $args[2], $args[3], $args[4], 0, '2026-01-01');
                $this->fail('expected rejection');
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_money_helpers(): void
    {
        $this->assertSame(1250, Money::toMinor('12.5'));
        $this->assertSame(12500000, Money::toMinor('125,000'));
        $this->assertSame(3, Money::round('2.5'));
        $this->assertSame(2, Money::round('2.49'));
        $this->assertSame(200, Money::bp(10000, 200));
        $this->assertSame('MK 1,500', Money::format(150000));
    }
}
