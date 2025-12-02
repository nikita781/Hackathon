<?php

namespace App\Notifications;

use App\Http\Resources\HackathonResource;
use App\Models\Support;
use Illuminate\Notifications\Notification;

class SupportAnsweredNotification extends Notification
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
            'title' => __('ticket_reply_title'),
            'description' => __('ticket_reply_description', ['closer_nickname' => $this->support->closer->nickname, 'hackathon_title' => $this->support->hackathon->title]),
            'support_id' => $this->support->id,
            'type' => $this->support->type,
            'message' => $this->support->messages()->latest()->first()?->message,
            'hackathon' => new HackathonResource($this->support->hackathon),
            'send_at' => now()->toDateString(),
        ];
    }
}
