<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Africa's Talking bulk SMS. Needs AFRICASTALKING_USERNAME and AFRICASTALKING_API_KEY. */
class AfricasTalkingSmsGateway implements SmsGateway
{
    public function __construct(private string $username, private string $apiKey, private string $senderId) {}

    public function send(string $to, string $message): string
    {
        if ($this->username === '' || $this->apiKey === '') {
            throw new RuntimeException('Africas Talking SMS is not configured.');
        }

        $res = Http::asForm()
            ->withHeaders(['apiKey' => $this->apiKey, 'Accept' => 'application/json'])
            ->post('https://api.africastalking.com/version1/messaging', ['username' => $this->username, 'to' => $to, 'message' => $message, 'from' => $this->senderId])
            ->throw()
            ->json();

        return (string) data_get($res, 'SMSMessageData.Recipients.0.messageId', '');
    }
}
