<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('1'),
        ]);

        $org = User::create([
            'name' => 'Organizer',
            'email' => 'org@org.com',
            'password' => Hash::make('1'),
        ]);

        $gsk = User::create([
            'name' => 'GSK',
            'email' => 'gsk@gsk.com',
            'password' => Hash::make('1'),
        ]);

        $member = User::create([
            'name' => 'Member',
            'email' => 'member@member.com',
            'password' => Hash::make('1'),
        ]);

        $admin->assignedRole(Role::SUPER_ADMIN);
        $org->assignedRole(Role::ORGANIZER);
        $gsk->assignedRole(Role::GSK);
        $member->assignedRole(Role::MEMBER);

        User::factory(30)->create();
    }
}
