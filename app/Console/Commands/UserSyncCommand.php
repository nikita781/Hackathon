<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Telescope;

class UserSyncCommand extends Command
{
    protected $signature = 'users:sync';
    protected $description = 'Синхронизация пользователей с основной БД';

    public function handle(): void
    {
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }

        $this->info('Начинаем синхронизацию пользователей...');

        $countCreated = 0;
        $countUpdated = 0;

        DB::connection('main_site')
            ->table('users')
            ->orderBy('id')
            ->chunk(500, function ($mainUsers) use (&$countCreated, &$countUpdated) {
                foreach ($mainUsers as $mainUser) {
                    $localUser = DB::table('users')->where('email', $mainUser->email)->first();

                    if (!$localUser) {
                        DB::table('users')->insert([
                            'id' => $mainUser->id,
                            'name' => $mainUser?->fio,
                            'nickname' => $mainUser->name,
                            'email' => $mainUser->email,
                            'password' => $mainUser->password,
                            'birthday' => $mainUser?->birthday,
                            'photo' => $mainUser?->photo,
                            'status' => User::STATUS_ACTIVE,
                            'created_at' => now(),
                            'updated_at' => $mainUser->updated_at,
                        ]);
                        $countCreated++;
                    } else {
                        if (Carbon::parse($mainUser->updated_at)->gt(Carbon::parse($localUser->updated_at))) {
                            DB::connection('pgsql')
                                ->table('users')
                                ->where('id', $localUser->id)
                                ->update([
                                    'name' => $mainUser?->fio,
                                    'nickname' => $mainUser->name,
                                    'email' => $mainUser->email,
                                    'password' => $mainUser->password,
                                    'birthday' => $mainUser?->birthday,
                                    'photo' => $mainUser?->photo,
                                    'status' => User::STATUS_ACTIVE,
                                    'updated_at' => $mainUser->updated_at,
                                ]);
                            $countUpdated++;
                        }
                    }
                }
            });
        $this->info("Синхронизация завершена. Создано: {$countCreated}, обновлено: {$countUpdated}");
    }
}
