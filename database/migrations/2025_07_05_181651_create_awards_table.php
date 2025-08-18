<?php

use App\Models\Award;
use App\Models\Hackathon;
use App\Models\User;
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
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hackathon::class)->nullable()->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('place')->nullable();
            $table->boolean('for_all')->default(false);
            $table->boolean('system')->default(false);
            $table->timestamps();
        });

        Schema::create('award_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Award::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->index()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamp('awarded_at')->nullable();
        });

        Award::create([
            'title' => 'Первый хакатон',
            'description' => 'Первый хакатон, в котором вы участвовали',
            'system' => true,
        ]);

        Award::create([
            'title' => '10-й хакатон',
            'description' => '10-й хакатон, в котором вы участвовали',
            'system' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awards');
        Schema::dropIfExists('award_user');
    }
};
