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

        $hackathon->assignPlaces();

        $hackathon->load('members', 'awards', 'teams.users');
        $hackathonAwards = $hackathon->awards;
        foreach ($hackathon->members as $member) {
            $member->notify(new HackathonFinishedNotification($hackathon));

            $hasParticipatedBefore = $member->awards()
                ->where('award_id', Award::SYSTEM_AWARD_FIRST)
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

        $awardedAt = now();

        foreach ($hackathonAwards as $award) {
            if ($award->for_all) {
                $award->users()->syncWithoutDetaching(
                    $hackathon->members->pluck('id')->mapWithKeys(fn ($id) => [$id => ['awarded_at' => now()]])
                );
            } elseif ($award->place !== null) {
                $winningUserIds = $hackathon->teams()
                    ->where('place', $award->place)
                    ->with('users:id')
                    ->get()
                    ->pluck('users.*.id')
                    ->flatten()
                    ->unique();

                $award->users()->syncWithoutDetaching(
                    $winningUserIds->mapWithKeys(fn ($id) => [$id => ['awarded_at' => $awardedAt]])
                );
            }
        }
    }
}
