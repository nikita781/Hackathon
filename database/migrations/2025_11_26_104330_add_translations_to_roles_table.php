<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->jsonb('translations')->nullable();
            $table->string('locale', 5)->default('ru');
        });

        DB::table('roles')->get()->each(function ($role) {
            $translations = match($role->id) {
                1 => [
                    'en' => 'Super Admin',
                    'de' => 'Super-Administrator',
                    'fr' => 'Super Administrateur',
                    'es' => 'Super Administrador',
                    'zh_CN' => '超级管理员',
                    'pt_PT' => 'Super Administrador',
                ],
                2 => [
                    'en' => 'Admin',
                    'de' => 'Administrator',
                    'fr' => 'Administrateur',
                    'es' => 'Administrador',
                    'zh_CN' => '管理员',
                    'pt_PT' => 'Administrador',
                ],
                3 => [
                    'en' => 'Moderator',
                    'de' => 'Moderator',
                    'fr' => 'Modérateur',
                    'es' => 'Moderador',
                    'zh_CN' => '版主',
                    'pt_PT' => 'Moderador',
                ],
                4 => [
                    'en' => 'Organizer',
                    'de' => 'Organisator',
                    'fr' => 'Organisateur',
                    'es' => 'Organizador',
                    'zh_CN' => '组织者',
                    'pt_PT' => 'Organizador',
                ],
                5 => [
                    'en' => 'Judge',
                    'de' => 'Richter',
                    'fr' => 'Juge',
                    'es' => 'Juez',
                    'zh_CN' => '评委',
                    'pt_PT' => 'Juiz',
                ],
                6 => [
                    'en' => 'Mentor',
                    'de' => 'Mentor',
                    'fr' => 'Mentor',
                    'es' => 'Mentor',
                    'zh_CN' => '导师',
                    'pt_PT' => 'Mentor',
                ],
                7 => [
                    'en' => 'Participant',
                    'de' => 'Teilnehmer',
                    'fr' => 'Participant',
                    'es' => 'Participante',
                    'zh_CN' => '参与者',
                    'pt_PT' => 'Participante',
                ],
                default => [],
            };

            if ($translations) {
                DB::table('roles')->where('id', $role->id)->update([
                    'translations' => json_encode($translations),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('translations');
            $table->dropColumn('locale', 5);
        });
    }
};
