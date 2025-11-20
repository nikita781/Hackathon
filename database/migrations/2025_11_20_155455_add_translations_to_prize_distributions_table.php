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
        Schema::table('prize_distributions', function (Blueprint $table) {
            $table->jsonb('translations')->nullable();
            $table->string('locale', 5)->default('ru');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prize_distributions', function (Blueprint $table) {
            $table->dropColumn('translations');
            $table->dropColumn('locale', 5);
        });
    }
};
