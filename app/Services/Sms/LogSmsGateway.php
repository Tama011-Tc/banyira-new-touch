<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Development driver: writes SMS to the log instead of sending. */
class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): string
    {
        Log::info("[SMS to {$to}] {$message}");

        return 'log-'.Str::random(10);
    }
}
