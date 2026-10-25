<?php

namespace App\Services\Sms;

/** The only thing the platform needs from an SMS gateway. A real adapter replaces MockSmsProvider; nothing else changes. */
interface SmsProvider
{
    public function name(): string;

    /** True for providers that do not really deliver. The UI labels such messages as simulated. */
    public function isSimulated(): bool;

    /** @return array{ref: string, status: string} */
    public function send(string $toE164, string $body): array;
}
