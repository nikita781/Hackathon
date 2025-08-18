<?php

namespace App\Notifications;

use App\Models\Support;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSupportNotification extends Notification
{
    public Support $support;

    public function __construct(Support $support)
    {
        $this->support = $support;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'support_id' => $this->support->id,
            'type' => $this->support->type,
            'message' => $this->support->messages()->latest()->first()?->message,
            'hackathon_id' => $this->support->hackathon_id,
        ];
    }
}
