<?php

namespace App\Jobs;

use App\Contracts\SmsGateway;
use App\Models\SmsMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendSms implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public int $smsId) {}

    public function handle(SmsGateway $gateway): void
    {
        $sms = SmsMessage::findOrFail($this->smsId);
        if ($sms->status === 'sent') {
            return; // idempotent on retry
        }
        $sms->update(['provider_reference' => $gateway->send($sms->to, $sms->body), 'status' => 'sent']);
    }

    public function failed(Throwable $e): void
    {
        SmsMessage::whereKey($this->smsId)->update(['status' => 'failed']);
    }
}
