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
            $table->smallInteger('min_team_size')->nullable();
            $table->smallInteger('max_team_size')->nullable();
            $table->dateTime('registration_start')->default(now());
            $table->dateTime('registration_end');
            $table->dateTime('event_start');
            $table->dateTime('event_end');
            $table->enum('prize_type', ['cash', 'non-cash']);
            $table->integer('prize_pool');
            $table->dateTime('work_time_start')->nullable();
            $table->dateTime('work_time_end')->nullable();
            $table->dateTime('evaluation_start')->nullable();
            $table->dateTime('evaluation_end')->nullable();
            $table->string('slug')->unique();
            $table->enum('status', ['draft', 'moderation', 'published', 'blocked'])->default('draft');
            $table->dateTime('moderated_time')->nullable();
            $table->dateTime('published_time')->nullable();
            $table->dateTime('blocked_time')->nullable();
            $table->string('comment')->nullable();
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
