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
            'nickname' => 'SAdmin',
            'email' => 'SAdmin@SAdmin.com',
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'nickname' => 'admin',
            'email' => 'admin@admin.com',
        ]);

        $moderator = User::create([
            'name' => 'Moderator',
            'nickname' => 'moderator',
            'email' => 'moderator@moderator.com',
        ]);

        $org = User::create([
            'name' => 'Organizer',
            'nickname' => 'org',
            'email' => 'org@org.com',
        ]);

        $judge = User::create([
            'name' => 'JUDGE',
            'nickname' => 'judge',
            'email' => 'judge@judge.com',
        ]);

        $mentor = User::create([
            'name' => 'MENTOR',
            'nickname' => 'mentor',
            'email' => 'mentor@mentor.com',
        ]);

        $member = User::create([
            'name' => 'Member',
            'nickname' => 'member',
            'email' => 'member@member.com',
        ]);

        $superAdmin->assignedRole(Role::SUPER_ADMIN);
        $admin->assignedRole(Role::ADMIN);
        $moderator->assignedRole(Role::MODERATOR);
        $org->assignedRole(Role::ORGANIZER);
        $judge->assignedRole(Role::JUDGE);
        $mentor->assignedRole(Role::MENTOR);
        $judge->assignedRole(Role::MEMBER);
        $mentor->assignedRole(Role::MEMBER);
        $member->assignedRole(Role::MEMBER);
    }
}
