<?php

namespace Tests\Unit;

use App\Support\Money;
use App\Support\Totp;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_rounding_is_half_away_from_zero(): void
    {
        $this->assertSame('10.01', Money::round('10.005'));
        $this->assertSame('10.00', Money::round('10.0049'));
        $this->assertSame('-10.01', Money::round('-10.005'));
    }

    public function test_percentages_use_decimal_arithmetic(): void
    {
        $this->assertSame('240000.00', Money::pct('6000000', '4'));
        $this->assertSame('0.30', Money::add('0.1', '0.2'));
    }

    public function test_amortisation_repays_exact_principal(): void
    {
        foreach ([['2500000', '21', 18], ['1000000', '36', 12], ['999999.99', '0', 7], ['3000000', '18.5', 36]] as [$p, $r, $n]) {
            $rows = Money::amortise($p, $r, $n);
            $this->assertCount($n, $rows);
            $this->assertSame(Money::of($p), Money::add(...array_column($rows, 'principal')), "principal for {$p} @ {$r}%");
            foreach ($rows as $row) {
                $this->assertGreaterThanOrEqual(0, (float) $row['principal']);
            }
        }
    }

    public function test_level_payment_matches_formula(): void
    {
        $rows = Money::amortise('1000000', '24', 12);   // 2% a month
        $this->assertSame('94559.60', Money::add($rows[0]['principal'], $rows[0]['interest']));
        $this->assertSame('20000.00', $rows[0]['interest']);
    }

    public function test_totp_matches_rfc6238_vector(): void
    {
        // RFC 6238 SHA-1 test secret "12345678901234567890" in base32, T = 59 s.
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->assertSame('287082', Totp::code($secret, 59));
        $this->assertTrue(Totp::verify($secret, '287082', 59));
        $this->assertFalse(Totp::verify($secret, '000000', 59));
    }
}
