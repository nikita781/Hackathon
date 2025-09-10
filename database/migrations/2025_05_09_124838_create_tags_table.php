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
            $table->smallInteger('order');
            $table->string('slug')->unique();
        });

        Schema::create('hackathon_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hackathon::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Tag::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });

        Tag::insert([
           ['title' => 'UX/UI', 'order' => 1, 'slug' => 'ux-ui'],
           ['title' => 'Тестировщики', 'order' => 2, 'slug' => 'testers'],
           ['title' => 'Веб-дизайнеры', 'order' => 3, 'slug' => 'web-designers'],
           ['title' => 'Product-менеджеры', 'order' => 4, 'slug' => 'product-managers'],
           ['title' => 'Веб-разработчики', 'order' => 5, 'slug' => 'web-developers'],
           ['title' => 'Android-разработчики', 'order' => 6, 'slug' => 'android-developers'],
           ['title' => 'iOS-разработчики', 'order' => 7, 'slug' => 'ios-developers'],
           ['title' => 'Frontend-разработчики', 'order' => 8, 'slug' => 'frontend-developers'],
           ['title' => 'Backend-разработчики', 'order' => 9, 'slug' => 'backend-developers'],
           ['title' => 'QA-инженеры', 'order' => 10, 'slug' => 'qa-engineers'],
           ['title' => 'DevOps', 'order' => 11, 'slug' => 'devops'],
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
