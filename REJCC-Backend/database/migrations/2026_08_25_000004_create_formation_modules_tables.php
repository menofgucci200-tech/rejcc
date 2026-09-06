<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contenu réel d'une formation : chaque module a un titre, une
        // description, et éventuellement une vidéo/un document à consulter
        // avant de le valider (remplace le simple compteur `modules_count`
        // pour les nouvelles formations — conservé pour compat descendante).
        Schema::create('formation_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formation_id')->constrained()->cascadeOnDelete();
            $table->string('titre', 200);
            $table->text('description')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->string('document_url', 500)->nullable();
            $table->string('duree', 50)->nullable(); // ex : « 15 min »
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('formation_module_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formation_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('formation_module_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->useCurrent();
            $table->unique(['formation_enrollment_id', 'formation_module_id'], 'enrollment_module_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formation_module_completions');
        Schema::dropIfExists('formation_modules');
    }
};
