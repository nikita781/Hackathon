<?php

use App\Models\Hackathon;
use App\Models\Tag;
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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('hackathon_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hackathon::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Tag::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });

        Tag::insert([
           ['title' => 'UX/UI'],
           ['title' => 'Тестировщики'],
           ['title' => 'Веб-дизайнеры'],
           ['title' => 'Product-менеджеры'],
           ['title' => 'Веб-разработчики'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
        Schema::dropIfExists('hackathon_tag');
    }
};
