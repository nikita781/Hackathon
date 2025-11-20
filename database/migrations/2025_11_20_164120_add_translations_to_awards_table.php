<?php

use App\Models\Award;
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
        Schema::table('awards', function (Blueprint $table) {
            $table->jsonb('translations')->nullable();
            $table->string('locale', 5)->default('ru');
        });

        $firstHackathon = Award::find(Award::SYSTEM_AWARD_FIRST);
        if ($firstHackathon) {
            $firstHackathon->updateQuietly([
                'translations' => [
                    'de' => [
                        'title' => 'Erster Hackathon',
                        'description' => 'Der erste Hackathon, an dem Sie teilgenommen haben'
                    ],
                    'en' => [
                        'title' => 'First Hackathon',
                        'description' => 'The first hackathon you participated in'
                    ],
                    'es' => [
                        'title' => 'Primer Hackathon',
                        'description' => 'El primer hackathon en el que participaste'
                    ],
                    'fr' => [
                        'title' => 'Premier Hackathon',
                        'description' => 'Le premier hackathon auquel vous avez participé'
                    ],
                    'pt' => [
                        'title' => 'Primeiro Hackathon',
                        'description' => 'O primeiro hackathon em que você participou'
                    ],
                    'zh_CN' => [
                        'title' => '首次黑客松',
                        'description' => '您参加的第一次黑客松'
                    ]
                ],
                'locale' => 'ru'
            ]);
        }

        $tenthHackathon = Award::find(Award::SYSTEM_AWARD_TEN);
        if ($tenthHackathon) {
            $tenthHackathon->updateQuietly([
                'translations' => [
                    'de' => [
                        'title' => '10. Hackathon',
                        'description' => 'Der 10. Hackathon, an dem Sie teilgenommen haben'
                    ],
                    'en' => [
                        'title' => '10th Hackathon',
                        'description' => 'The 10th hackathon you participated in'
                    ],
                    'es' => [
                        'title' => '10° Hackathon',
                        'description' => 'El 10° hackathon en el que participaste'
                    ],
                    'fr' => [
                        'title' => '10ème Hackathon',
                        'description' => 'Le 10ème hackathon auquel vous avez participé'
                    ],
                    'pt' => [
                        'title' => '10° Hackathon',
                        'description' => 'O 10° hackathon em que você participou'
                    ],
                    'zh_CN' => [
                        'title' => '第10次黑客松',
                        'description' => '您参加的第10次黑客松'
                    ]
                ],
                'locale' => 'ru'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->dropColumn('translations');
            $table->dropColumn('locale', 5);
        });
    }
};
