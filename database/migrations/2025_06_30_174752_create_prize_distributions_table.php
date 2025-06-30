<?php

use App\Models\Nomination;
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
        Schema::create('prize_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Nomination::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->smallInteger('place');
            $table->string('prize');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prize_distributions');
    }
};
