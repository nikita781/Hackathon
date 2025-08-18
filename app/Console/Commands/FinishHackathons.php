<?php

namespace App\Console\Commands;

use App\Events\HackathonFinished;
use App\Models\Hackathon;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FinishHackathons extends Command
{
    protected $signature = 'hackathons:finish';
    protected $description = 'Завершает хакатоны, которые кончились';

    public function handle(): void
    {
        $hackathons = Hackathon::where('status', Hackathon::STATUS_PUBLISHED)
            ->where('event_end', '<=', Carbon::now())
            ->where('is_finished', false)
            ->get();

        foreach ($hackathons as $hackathon) {
            event(new HackathonFinished($hackathon));
            $hackathon->update(['is_finished' => true]);
            $this->info("Хакатон {$hackathon->title} завершён");
        }
    }
}
