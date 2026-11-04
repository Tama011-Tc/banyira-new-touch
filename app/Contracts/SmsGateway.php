<?php

namespace App\Contracts;

interface SmsGateway
{
    /** Send one SMS. Returns the provider's message reference. */
    public function send(string $to, string $message): string;
}
