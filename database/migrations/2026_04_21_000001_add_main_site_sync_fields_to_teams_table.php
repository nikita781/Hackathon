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
        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('main_site_team_id')
                ->nullable()
                ->after('owner_id')
                ->unique();
            $table->string('sync_source', 64)
                ->nullable()
                ->after('main_site_team_id')
                ->index();
            $table->timestamp('synced_at')
                ->nullable()
                ->after('sync_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('synced_at');
            $table->dropIndex(['sync_source']);
            $table->dropColumn('sync_source');
            $table->dropUnique('teams_main_site_team_id_unique');
            $table->dropColumn('main_site_team_id');
        });
    }
};
