<?php

use App\Models\TabSection;
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
        Schema::create('tab_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TabSection::class)->index()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('title');
            $table->mediumText('content')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tab_items');
    }
};
