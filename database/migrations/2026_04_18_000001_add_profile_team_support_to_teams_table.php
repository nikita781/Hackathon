<?php

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
        Schema::table('teams', function (Blueprint $table) {
            $table
                ->foreignIdFor(User::class, 'owner_id')
                ->nullable()
                ->after('id')
                ->index()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        $this->setHackathonIdNullable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('teams')->whereNull('hackathon_id')->delete();

        $this->setHackathonIdNotNullable();

        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });
    }

    private function setHackathonIdNullable(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE teams ALTER COLUMN hackathon_id DROP NOT NULL'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE teams MODIFY hackathon_id BIGINT UNSIGNED NULL'),
            default => null,
        };
    }

    private function setHackathonIdNotNullable(): void
    {
        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE teams ALTER COLUMN hackathon_id SET NOT NULL'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE teams MODIFY hackathon_id BIGINT UNSIGNED NOT NULL'),
            default => null,
        };
    }
};
