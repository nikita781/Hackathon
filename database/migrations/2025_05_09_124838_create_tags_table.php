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
            $table->string('slug')->unique();
        });

        Schema::create('hackathon_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hackathon::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Tag::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });

        Tag::insert([
           ['title' => 'UX/UI', 'slug' => 'ux-ui'],
           ['title' => 'Тестировщики', 'slug' => 'testers'],
           ['title' => 'Веб-дизайнеры', 'slug' => 'web-designers'],
           ['title' => 'Product-менеджеры', 'slug' => 'product-managers'],
           ['title' => 'Веб-разработчики', 'slug' => 'web-developers'],
           ['title' => 'Android-разработчики', 'slug' => 'android-developers'],
           ['title' => 'iOS-разработчики', 'slug' => 'ios-developers'],
           ['title' => 'Frontend-разработчики', 'slug' => 'frontend-developers'],
           ['title' => 'Backend-разработчики', 'slug' => 'backend-developers'],
           ['title' => 'QA-инженеры', 'slug' => 'qa-engineers'],
           ['title' => 'DevOps', 'slug' => 'devops'],
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
