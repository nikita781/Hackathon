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
        $superAdmin = User::create([
            'name' => 'Главный Админ',
            'email' => 'SAdmin@SAdmin.com',
            'password' => Hash::make('1'),
        ]);

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

        $judge = User::create([
            'name' => 'JUDGE',
            'email' => 'judge@judge.com',
            'password' => Hash::make('1'),
        ]);

        $mentor = User::create([
            'name' => 'MENTOR',
            'email' => 'mentor@mentor.com',
            'password' => Hash::make('1'),
        ]);

        $member = User::create([
            'name' => 'Member',
            'email' => 'member@member.com',
            'password' => Hash::make('1'),
        ]);

        $superAdmin->assignedRole(Role::SUPER_ADMIN);
        $admin->assignedRole(Role::ADMIN);
        $org->assignedRole(Role::ORGANIZER);
        $judge->assignedRole(Role::JUDGE);
        $mentor->assignedRole(Role::MENTOR);
        $member->assignedRole(Role::MEMBER);
    }
}
