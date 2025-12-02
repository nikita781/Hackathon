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
            'title' => __('hackathon_finished_title'),
//            'description' => "Хакатон «{$this->hackathon->title}» завершился. Вы заняли {$this->place}-е место.",
            'description' => __('hackathon_finished_description',['hackathon_title' => $this->hackathon->title, 'place' => $this->place]),
            'hackathon' => new HackathonResource($this->hackathon),
            'url' => route('hackathons.show', $this->hackathon),
            'send_at' => now()->toDateString(),
        ];
    }
}
