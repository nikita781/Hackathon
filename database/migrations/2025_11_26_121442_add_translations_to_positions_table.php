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
        Schema::table('positions', function (Blueprint $table) {
            $table->jsonb('translations')->nullable();
            $table->string('locale', 5)->default('ru');
        });

        DB::table('positions')->get()->each(function ($position) {
            $translations = match($position->id) {
                1 => [
                    'en' => 'Captain',
                    'de' => 'Kapitän',
                    'fr' => 'Capitaine',
                    'es' => 'Capitán',
                    'zh_CN' => '队长',
                    'pt_PT' => 'Capitão',
                ],
                2 => [
                    'en' => 'Universal',
                    'de' => 'Allrounder',
                    'fr' => 'Universel',
                    'es' => 'Universal',
                    'zh_CN' => '全能型',
                    'pt_PT' => 'Universal',
                ],
                3 => [
                    'en' => 'Backend',
                    'de' => 'Backend',
                    'fr' => 'Backend',
                    'es' => 'Backend',
                    'zh_CN' => '后端',
                    'pt_PT' => 'Backend',
                ],
                4 => [
                    'en' => 'Frontend',
                    'de' => 'Frontend',
                    'fr' => 'Frontend',
                    'es' => 'Frontend',
                    'zh_CN' => '前端',
                    'pt_PT' => 'Frontend',
                ],
                5 => [
                    'en' => 'Designer',
                    'de' => 'Designer',
                    'fr' => 'Designer',
                    'es' => 'Diseñador',
                    'zh_CN' => '设计师',
                    'pt_PT' => 'Designer',
                ],
                6 => [
                    'en' => 'Fullstack',
                    'de' => 'Fullstack',
                    'fr' => 'Fullstack',
                    'es' => 'Fullstack',
                    'zh_CN' => '全栈',
                    'pt_PT' => 'Fullstack',
                ],
                7 => [
                    'en' => 'DevOps',
                    'de' => 'DevOps',
                    'fr' => 'DevOps',
                    'es' => 'DevOps',
                    'zh_CN' => 'DevOps',
                    'pt_PT' => 'DevOps',
                ],
                default => [],
            };

            if ($translations) {
                DB::table('positions')->where('id', $position->id)->update([
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
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('translations');
            $table->dropColumn('locale', 5);
        });
    }
};
