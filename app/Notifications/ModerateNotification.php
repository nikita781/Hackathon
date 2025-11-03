<?php

namespace App\Notifications;

use App\Http\Resources\HackathonResource;
use App\Http\Resources\ProjectResource;
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

    public function toArray(object $notifiable): array
    {
        return [
            'status' => $this->data['status'],
            'description' => $this->data['description'],
            'title' => $this->data['title'],
            'send_at' => $this->data['send_at'],
            'hackathon' => $this->data['hackathon'] ? new HackathonResource($this->data['hackathon']) : null,
            'project' => $this->data['project'],
        ];
    }
}
