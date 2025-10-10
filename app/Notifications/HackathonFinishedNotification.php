<?php

namespace App\Notifications;

use App\Http\Resources\HackathonResource;
use App\Models\Hackathon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HackathonFinishedNotification extends Notification
{
    use Queueable;

    public function __construct(public Hackathon $hackathon, public int $place) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Хакатон завершён',
            'description' => "Хакатон «{$this->hackathon->title}» завершился. Вы заняли {$this->place}-е место.",
            'hackathon' => new HackathonResource($this->hackathon),
            'url' => route('hackathons.show', $this->hackathon),
            'send_at' => now()->toDateString(),
        ];
    }
}
