<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Role::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });

        Role::insert([
            ['id' => Role::SUPER_ADMIN, 'title' => 'Главный админ'],
            ['id' => Role::ADMIN, 'title' => 'Админ'],
            ['id' => Role::MODERATOR, 'title' => 'Модератор'],
            ['id' => Role::ORGANIZER, 'title' => 'Организатор'],
            ['id' => Role::JUDGE, 'title' => 'Судья'],
            ['id' => Role::MENTOR, 'title' => 'Ментор'],
            ['id' => Role::MEMBER, 'title' => 'Участник'],
        ]);

        $superAdmin = User::create([
            'name' => 'Главный Админ',
            'nickname' => 'SuperAdmin',
            'email' => 'SuperAdmin@SuperAdmin.com',
        ]);

        $superAdmin->assignedRole(Role::SUPER_ADMIN);

        $admin = User::create([
            'name' => 'Админ',
            'nickname' => 'Admin',
            'email' => 'Admin@Admin.com',
        ]);

        $admin->assignedRole(Role::ADMIN);

        $moderator = User::create([
            'name' => 'Модератор',
            'nickname' => 'Moderator',
            'email' => 'Moderator@Moderator.com',
        ]);

        $moderator->assignedRole(Role::MODERATOR);

        $organizer = User::create([
            'name' => 'Организатор',
            'nickname' => 'Organizer',
            'email' => 'Organizer@Organizer.com',
        ]);

        $organizer->assignedRole(Role::ORGANIZER);

        $judge = User::create([
            'name' => 'Судья',
            'nickname' => 'Judge',
            'email' => 'Judge@Judge.com',
        ]);

        $judge->assignedRole(Role::JUDGE);

        $mentor = User::create([
            'name' => 'Ментор',
            'nickname' => 'Mentor',
            'email' => 'Mentor@Mentor.com',
        ]);

        $mentor->assignedRole(Role::MENTOR);

        $member = User::create([
            'name' => 'Участник',
            'nickname' => 'Member',
            'email' => 'Member@Member.com',
        ]);

        $member->assignedRole(Role::MEMBER);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
        Schema::dropIfExists('role_user');
    }
};
