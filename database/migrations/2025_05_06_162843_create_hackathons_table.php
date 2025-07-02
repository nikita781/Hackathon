<?php

use App\Models\Hackathon;
use App\Models\Role;
use App\Models\User;
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
        Schema::create('hackathons', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('title');
            $table->enum('format', ['online', 'offline', 'hybrid']);
            $table->enum('type', ['team', 'individual']);
            $table->smallInteger('min_team_size');
            $table->smallInteger('max_team_size');
            $table->date('registration_start')->default(now());
            $table->date('registration_end');
            $table->date('event_start');
            $table->date('event_end');
            $table->enum('prize_type', ['cash', 'non-cash']);
            $table->integer('prize_pool');
            $table->dateTime('work_time_start')->nullable();
            $table->dateTime('work_time_end')->nullable();
            $table->dateTime('evaluation_start')->nullable();
            $table->dateTime('evaluation_end')->nullable();
            $table->string('slug')->unique();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('hackathon_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Hackathon::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Role::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hackathons');
        Schema::dropIfExists('hackathon_user');
    }
};
