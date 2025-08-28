<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ModerateNotification extends Notification
{
    use Queueable;

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'status' => $this->data['status'],
            'comment' => $this->data['comment'],
            'message' => $this->data['message'],
        ];
    }
}
