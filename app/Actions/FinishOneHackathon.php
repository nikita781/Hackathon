<?php

namespace App\Actions;

use App\Events\HackathonFinished;
use App\Models\Hackathon;
use Illuminate\Support\Carbon;
use Laravel\Telescope\Telescope;

class FinishOneHackathon
{
    public function __invoke($hackathon_slug): bool
    {
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }

        $hackathon = Hackathon::where('status', Hackathon::STATUS_PUBLISHED)
            ->where('event_end', '<=', Carbon::now())
            ->where('is_finished', false)
            ->where('slug', $hackathon_slug)
            ->first();

        if (!isset($hackathon)) {
            return false;
        }

        $hackathon->update(['is_finished' => true]);
        event(new HackathonFinished($hackathon));

        return true;
    }
}
