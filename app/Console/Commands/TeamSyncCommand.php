<?php

namespace App\Console\Commands;

use App\Actions\SyncTeams;
use Illuminate\Console\Command;

class TeamSyncCommand extends Command
{
    protected $signature = 'teams:sync';
    protected $description = 'Синхронизация команд с основной БД';

    public function handle(SyncTeams $action): void
    {
        $this->info('Начинаем синхронизацию команд...');

        $counters = $action();

        $this->info(
            "Синхронизация завершена. Команд: {$counters['sync']}, участников: {$counters['members']}, удалено: {$counters['deleted']}"
        );
    }
}
