<?php

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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('oauth_id')->index()->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('nickname')->unique();
            $table->string('email')->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('tshort_size')->nullable();
            $table->string('favorite_programming_lang')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
