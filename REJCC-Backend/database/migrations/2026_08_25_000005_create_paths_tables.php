<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Parcours guidé : une séquence ordonnée de formations vers un
        // objectif (ex. « Parcours Entrepreneur Junior »). Le déblocage est
        // progressif : une formation ne s'ouvre qu'une fois la précédente
        // terminée. Le badge de fin de parcours est calculé à la volée
        // (comme les certificats) plutôt que stocké.
        Schema::create('paths', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('slug', 160)->unique();
            $table->text('description')->nullable();
            $table->string('objectif', 300)->nullable();
            $table->string('badge_icon', 40)->nullable();
            $table->string('badge_couleur', 20)->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('path_formation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('formation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
            $table->unique(['path_id', 'formation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('path_formation');
        Schema::dropIfExists('paths');
    }
};
