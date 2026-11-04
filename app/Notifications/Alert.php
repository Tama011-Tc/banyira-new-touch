<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification type for platform events: stored in-app, emailed when the
 * address is verified, and sent by SMS for the events that matter most
 * (application updates, offers, funding progress, repayments).
 */
class Alert extends Notification
{
    public function __construct(public string $title, public string $body, public ?string $url = null, public bool $sms = false) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->email_verified_at) {
            $channels[] = 'mail';
        }
        if ($this->sms && $notifiable->phone_verified_at) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title)->line($this->body);

        return $this->url ? $mail->action('Open Banyira', url($this->url)) : $mail;
    }

    public function toSms(object $notifiable): string
    {
        return "Banyira: {$this->title}. {$this->body}";
    }
}
