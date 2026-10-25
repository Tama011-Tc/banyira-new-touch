<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Lets a business owner set their OWN password. Staff never know it. */
class OwnerInvitation extends Notification
{
    public function __construct(private readonly string $link, private readonly string $repName) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = config('banyira.brand');

        return (new MailMessage)->subject("You've been invited to {$brand}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->repName} from {$brand} has set up an account for you so you can follow your financing application.")
            ->line('Choose your own password using the button below. The link expires in 3 days.')
            ->action('Set my password', $this->link)
            ->line('Approval and returns are never guaranteed. If you were not expecting this, ignore this email.');
    }
}
