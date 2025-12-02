<?php

namespace App\Notifications;

use App\Http\Resources\HackathonResource;
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
            'title' => __('new_ticket_title'),
            'description' => 'Пользователь ждет вашего ответа',
            'hackathon' => new HackathonResource($this->support->hackathon),
            'send_at' => now()->toDateString(),
        ];
    }
}
