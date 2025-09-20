<?php

namespace App\Console\Commands;

use App\Events\HackathonFinished;
use App\Models\Hackathon;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Laravel\Telescope\Telescope;

class FinishHackathons extends Command
{
    protected $signature = 'hackathons:finish';
    protected $description = 'Завершает хакатоны, которые кончились';

    public function handle(\App\Actions\FinishHackathons $action): void
    {
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }

        $hackathonTitles = $action();

        $this->info('Завершены хакатоны: "'.implode('", "', $hackathonTitles).'"');
    }
}
