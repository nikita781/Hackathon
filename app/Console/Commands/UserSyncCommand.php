<?php

namespace App\Console\Commands;

use App\Actions\SyncUsers;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Telescope;

class UserSyncCommand extends Command
{
    protected $signature = 'users:sync';
    protected $description = 'Синхронизация пользователей с основной БД';

    public function handle(SyncUsers $action): void
    {
        $this->info('Начинаем синхронизацию пользователей...');

        $counters = $action();

        $this->info("Синхронизация завершена. Синхронизировано: {$counters['sync']}");
    }
}
