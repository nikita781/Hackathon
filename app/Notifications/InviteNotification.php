<?php

namespace App\Notifications;

use App\Http\Resources\HackathonResource;
use App\Http\Resources\ProjectResource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteNotification extends Notification
{
    use Queueable;

    private array $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->data['title'],
            'description' => $this->data['description'],
            'url' => $this->data['url'],
            'send_at' => $this->data['send_at'],
            'is_active' => $this->data['is_active'],
            'hackathon' => $this->data['hackathon'] ? new HackathonResource($this->data['hackathon']) : null,
        ];
    }
}
