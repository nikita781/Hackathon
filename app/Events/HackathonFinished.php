<?php

namespace App\Events;

use App\Models\Hackathon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HackathonFinished
{
    use Dispatchable, SerializesModels;

    public Hackathon $hackathon;

    public function __construct(Hackathon $hackathon)
    {
        $this->hackathon = $hackathon;
    }
}
