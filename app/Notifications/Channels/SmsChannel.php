<?php

namespace App\Notifications\Channels;

use App\Jobs\SendSms;
use App\Models\SmsMessage;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable->phone || ! method_exists($notification, 'toSms')) {
            return;
        }

        $sms = SmsMessage::create([
            'user_id' => $notifiable->id,
            'to' => $notifiable->phone,
            'body' => mb_substr($notification->toSms($notifiable), 0, 480),
            'driver' => config('banyira.sms.driver'),
        ]);
        SendSms::dispatch($sms->id);
    }
}
