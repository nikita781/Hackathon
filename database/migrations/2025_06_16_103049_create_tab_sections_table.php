<?php

use App\Models\Tab;
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
        Schema::create('tab_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tab::class)->index()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('title');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tab_sections');
    }
};
