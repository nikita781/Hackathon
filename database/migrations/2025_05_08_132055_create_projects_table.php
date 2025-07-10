<?php

use App\Models\Hackathon;
use App\Models\Team;
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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hackathon::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Team::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
//            $table->string('preview_path');
            $table->text('about')->nullable();
            $table->string('stack')->nullable();
            $table->string('project_link')->nullable();
//            $table->string('presentation_path')->nullable();
            $table->string('video_link')->nullable();
            $table->enum('status', ['draft', 'moderation', 'published', 'blocked'])->default('draft');
            $table->dateTime('moderated_time')->nullable();
            $table->dateTime('published_time')->nullable();
            $table->dateTime('blocked_time')->nullable();
            $table->float('avg_score')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
