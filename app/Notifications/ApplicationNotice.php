<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** An in-app notice about an application: new work for the credit team, or progress for the applicant. */
class ApplicationNotice extends Notification
{
    public function __construct(private readonly string $title, private readonly string $body, private readonly string $url, private readonly string $kind = 'application') {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => $this->url, 'kind' => $this->kind];
    }
}
