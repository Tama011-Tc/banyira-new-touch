<?php

namespace App\Services\Sms;

use Illuminate\Support\Str;

/** Delivers nothing. The message is only recorded (sms_messages) so the demo can show what would have been sent. */
class MockSmsProvider implements SmsProvider
{
    public function name(): string
    {
        return 'mock';
    }

    public function isSimulated(): bool
    {
        return true;
    }

    public function send(string $toE164, string $body): array
    {
        return ['ref' => 'MOCK-'.strtoupper(Str::random(10)), 'status' => 'sent'];
    }
}
