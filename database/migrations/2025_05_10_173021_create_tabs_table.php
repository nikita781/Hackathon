<?php

use App\Models\Hackathon;
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
        Schema::create('tabs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hackathon::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('title', ['Обзор', 'Ресурсы', 'Правила', 'Контакты']);
            $table->mediumText('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabs');
    }
};
