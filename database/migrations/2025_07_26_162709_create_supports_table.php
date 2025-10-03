<?php

use App\Models\Hackathon;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Hackathon::class)->index()->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('type', ['question', 'suggestion', 'bug'])->default('question')->index();
            $table->boolean('is_completed')->default(false)->index();
            $table->foreignId('closed_by')->nullable()->index()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supports');
    }
};
