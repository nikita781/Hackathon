<?php

namespace App\Notifications;

use App\Models\Hackathon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HackathonFinishedNotification extends Notification
{
    use Queueable;

    public function __construct(public Hackathon $hackathon) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Хакатон завершён',
            'description' => "Хакатон «{$this->hackathon->title}» завершился. Узнайте своё место!",
            'url' => route('hackathons.show', $this->hackathon),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
