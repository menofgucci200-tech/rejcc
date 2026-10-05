<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Badges de parcours obtenus : conservés même si le parcours évolue ensuite. */
    public function up(): void
    {
        Schema::create('path_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('path_id')->constrained()->cascadeOnDelete();
            $table->timestamp('obtenu_at');
            $table->timestamps();
            $table->unique(['user_id', 'path_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('path_badges');
    }
};
