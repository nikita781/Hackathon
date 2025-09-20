<?php

namespace App\Actions;

use App\Events\HackathonFinished;
use App\Models\Hackathon;
use Illuminate\Support\Carbon;
use Laravel\Telescope\Telescope;

class FinishHackathons
{
    public function __invoke(): array
    {
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }

        $response = [];

        $hackathons = Hackathon::where('status', Hackathon::STATUS_PUBLISHED)
            ->where('event_end', '<=', Carbon::now())
            ->where('is_finished', false)
            ->get();

        foreach ($hackathons as $hackathon) {
            event(new HackathonFinished($hackathon));
            $hackathon->update(['is_finished' => true]);
            $response[] = $hackathon->title;
        }

        return $response;
    }
}
