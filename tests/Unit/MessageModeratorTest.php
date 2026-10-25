<?php

namespace Tests\Unit;

use App\Support\MessageModerator as M;
use PHPUnit\Framework\TestCase;

class MessageModeratorTest extends TestCase
{
    /** @dataProvider blocked */
    public function test_contact_details_and_payment_instructions_are_blocked(string $text, string $rule): void
    {
        $r = M::inspect($text);
        $this->assertSame(M::BLOCK, $r['action'], $text);
        $this->assertContains($rule, array_column($r['matches'], 'rule'), $text);
    }

    public static function blocked(): array
    {
        return [
            'intl phone' => ['Call me on +265 999 123 456 tomorrow', 'phone'],
            'local phone' => ['My number is 0888 123 456', 'phone'],
            'run-on phone' => ['0999123456', 'phone'],
            'dotted phone' => ['reach me 099.912.3456', 'phone'],
            'email' => ['write to john.banda@gmail.com please', 'email'],
            'obfuscated email' => ['john (at) gmail (dot) com', 'email'],
            'url' => ['see https://example.com/me', 'url'],
            'bare domain' => ['visit mybusiness.mw for details', 'url'],
            'whatsapp' => ['let us continue on WhatsApp', 'messaging_app'],
            'telegram' => ['add me on telegram', 'messaging_app'],
            'handle' => ['I am @john_banda on there', 'handle'],
            'mobile money' => ['send it to my Airtel Money', 'payment_instruction'],
            'bank account' => ['my account number is 1234', 'payment_instruction'],
            'direct pay' => ['just pay me directly', 'payment_instruction'],
        ];
    }

    /** @dataProvider allowed */
    public function test_ordinary_business_talk_is_not_blocked(string $text): void
    {
        $this->assertSame(M::ALLOW, M::inspect($text)['action'], $text);
    }

    public static function allowed(): array
    {
        return [
            ['We need MK 5,000,000 over 24 months'],
            ['Revenue was MWK 1,500,000.50 last month'],
            ['We keep 3,500 layers and sell 12,000 eggs weekly'],
            ['Founded in 2019, we employ 14 people in Mzuzu'],
            ['Could the rate be 22.5% with a 2 month grace period?'],
            ['Feed costs about 1,400,000 kwacha a month'],
            ['Delivery takes 3 to 5 days from the Lilongwe depot'],
        ];
    }

    public function test_off_platform_requests_go_to_review_not_hard_block(): void
    {
        $r = M::inspect('Can we talk privately about this outside the platform?');
        $this->assertSame(M::REVIEW, $r['action']);
    }

    public function test_rules_can_be_switched_off(): void
    {
        $this->assertSame(M::ALLOW, M::inspect('mybusiness.mw', ['email', 'phone'])['action']);
        $this->assertSame(M::BLOCK, M::inspect('mybusiness.mw', ['url'])['action']);
    }
}
