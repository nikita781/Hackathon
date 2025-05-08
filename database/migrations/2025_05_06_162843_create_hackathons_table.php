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
        Schema::create('hackathons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_path');
            $table->enum('format', ['online', 'offline', 'hybrid']);
            $table->enum('type', ['team', 'individual']);
            $table->smallInteger('min_team_size');
            $table->smallInteger('max_team_size');
            $table->date('registration_start')->default(now());
            $table->date('registration_end');
            $table->date('event_start');
            $table->date('event_end');
            $table->decimal('prize_pool', 12, 2);
            $table->string('slug')->unique();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hackathons');
    }
};
