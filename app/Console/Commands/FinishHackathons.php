<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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

        if ($hackathonTitles) {
            Log::info('Завершены хакатоны: "'.implode('", "', $hackathonTitles).'"');
        }
    }
}
