<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Telescope;

class SyncUsers
{
    public function __invoke(): array
    {
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }

        $countSync = 0;

       DB::connection('main_site')
            ->table('users')
            ->orderBy('id')
            ->chunk(500, function ($mainUsers) use (&$countSync) {
                $records = [];
                foreach ($mainUsers as $mainUser) {
                    $records[] = [
                        'id' => $mainUser->id,
                        'name' => $mainUser?->fio,
                        'nickname' => $mainUser->name,
                        'email' => $mainUser->email,
                        'password' => $mainUser->password,
                        'phone_number' => $mainUser->telephone,
                        'birthday' => $mainUser?->birthday,
                        'photo' => $mainUser?->photo,
                        'status' => User::STATUS_ACTIVE,
                        'created_at' => now(),
                        'updated_at' => $mainUser->updated_at,
                    ];
                }

                $count = DB::table('users')->upsert(
                    $records,
                    ['id'],
                    [
                        'name',
                        'nickname',
                        'email',
                        'password',
                        'phone_number',
                        'birthday',
                        'photo',
                        'status',
                        'updated_at',
                    ]
                );

                $countSync += $count;
            });

        DB::statement("SELECT setval('users_id_seq', (SELECT MAX(id) FROM users))");

        return [
            'sync' => $countSync
        ];
    }
}
