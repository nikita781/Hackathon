<?php

namespace App\Listeners;

use App\Events\HackathonFinished;
use App\Models\Award;
use App\Notifications\HackathonFinishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendHackathonFinishedNotifications
{
    public function __construct()
    {
        //
    }

    public function handle(HackathonFinished $event): void
    {
        $hackathon = $event->hackathon;

        $hackathon->load('members');
        foreach ($hackathon->members as $member) {
            $member->notify(new HackathonFinishedNotification($hackathon));

            $hasParticipatedBefore = $member->hackathons()
                ->where('hackathon_id', '!=', $hackathon->id)
                ->where('event_end', '<', now())
                ->exists();

            if (!$hasParticipatedBefore) {
                $award = Award::where('system', true)
                    ->where('id', Award::SYSTEM_AWARD_FIRST)
                    ->first();

                if ($award && !$member->awards()->where('award_id', $award->id)->exists()) {
                    $member->awards()->attach($award->id, ['awarded_at' => now()]);
                }
            }
        }
    }
}
