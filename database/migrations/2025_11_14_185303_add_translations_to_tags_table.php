<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->jsonb('translations')->nullable();
            $table->string('locale', 5)->default('ru');
        });

        DB::table('tags')->get()->each(function ($tag) {
            $translations = match($tag->slug) {
                'ux-ui' => [
                    'en' => 'UX/UI',
                    'de' => 'UX/UI',
                    'fr' => 'UX/UI',
                    'es' => 'UX/UI',
                    'zh_CN' => 'UX/UI',
                    'pt_PT' => 'UX/UI',
                ],
                'testers' => [
                    'en' => 'Testers',
                    'de' => 'Tester',
                    'fr' => 'Testeurs',
                    'es' => 'Probadores',
                    'zh_CN' => '测试人员',
                    'pt_PT' => 'Testadores',
                ],
                'web-designers' => [
                    'en' => 'Web Designers',
                    'de' => 'Webdesigner',
                    'fr' => 'Web designers',
                    'es' => 'Diseñadores web',
                    'zh_CN' => '网页设计师',
                    'pt_PT' => 'Designers Web',
                ],
                'product-managers' => [
                    'en' => 'Product Managers',
                    'de' => 'Produktmanager',
                    'fr' => 'Chefs de produit',
                    'es' => 'Product managers',
                    'zh_CN' => '产品经理',
                    'pt_PT' => 'Gestores de Produto',
                ],
                'web-developers' => [
                    'en' => 'Web Developers',
                    'de' => 'Webentwickler',
                    'fr' => 'Développeurs web',
                    'es' => 'Desarrolladores web',
                    'zh_CN' => '网页开发者',
                    'pt_PT' => 'Programadores Web',
                ],
                'android-developers' => [
                    'en' => 'Android Developers',
                    'de' => 'Android-Entwickler',
                    'fr' => 'Développeurs Android',
                    'es' => 'Desarrolladores Android',
                    'zh_CN' => 'Android 开发者',
                    'pt_PT' => 'Programadores Android',
                ],
                'ios-developers' => [
                    'en' => 'iOS Developers',
                    'de' => 'iOS-Entwickler',
                    'fr' => 'Développeurs iOS',
                    'es' => 'Desarrolladores iOS',
                    'zh_CN' => 'iOS 开发者',
                    'pt_PT' => 'Programadores iOS',
                ],
                'frontend-developers' => [
                    'en' => 'Frontend Developers',
                    'de' => 'Frontend-Entwickler',
                    'fr' => 'Développeurs frontend',
                    'es' => 'Desarrolladores frontend',
                    'zh_CN' => '前端开发者',
                    'pt_PT' => 'Programadores Frontend',
                ],
                'backend-developers' => [
                    'en' => 'Backend Developers',
                    'de' => 'Backend-Entwickler',
                    'fr' => 'Développeurs backend',
                    'es' => 'Desarrolladores backend',
                    'zh_CN' => '后端开发者',
                    'pt_PT' => 'Programadores Backend',
                ],
                'qa-engineers' => [
                    'en' => 'QA Engineers',
                    'de' => 'QA-Ingenieure',
                    'fr' => 'Ingénieurs QA',
                    'es' => 'Ingenieros QA',
                    'zh_CN' => 'QA 工程师',
                    'pt_PT' => 'Engenheiros QA',
                ],
                'devops' => [
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
                DB::table('tags')->where('id', $tag->id)->update([
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
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('translations');
            $table->dropColumn('locale');
        });
    }
};
