<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('category', 100);
            $table->text('description')->nullable();
            $table->string('duration', 50)->nullable(); // ex : « 4 semaines »
            $table->string('level', 50)->nullable();    // ex : « Débutant »
            $table->boolean('is_free')->default(true);
            $table->boolean('is_certifying')->default(false);
            $table->unsignedTinyInteger('modules_count')->default(1);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('formation_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('progress')->default(0); // 0-100
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['formation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formation_enrollments');
        Schema::dropIfExists('formations');
    }
};
