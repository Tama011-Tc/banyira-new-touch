<?php

namespace Tests\Unit;

use App\Services\FeeService;
use PHPUnit\Framework\TestCase;

class FeeServiceTest extends TestCase
{
    public function test_fees_come_from_a_disclosed_snapshot(): void
    {
        $fees = ['origination_bp' => 200, 'investor_service_bp' => 100, 'processing_bp' => 150, 'tax_bp' => 0];
        $f = FeeService::onPrincipal($fees, 100000000);
        $this->assertSame(2000000, $f['origination']);
        $this->assertSame(1000000, $f['investor_service']);
        $this->assertSame(98000000, $f['net_to_business']);
        $this->assertSame(101000000, $f['investor_pays']);
        $this->assertSame(1500000, FeeService::processing($fees, 100000000));
    }

    public function test_tax_is_added_on_top_of_each_fee_and_rounded(): void
    {
        $f = FeeService::onPrincipal(['origination_bp' => 200, 'investor_service_bp' => 100, 'processing_bp' => 0, 'tax_bp' => 1650], 10000001);
        $this->assertSame(200000, $f['origination']);            // 2% of 10,000,001 = 200,000.02 -> 200,000
        $this->assertSame(33000, $f['origination_tax']);          // 16.5% of 200,000
        $this->assertSame(10000001 - 200000 - 33000, $f['net_to_business']);
        $this->assertSame(10000001 + 100000 + 16500, $f['investor_pays']);
    }
}
