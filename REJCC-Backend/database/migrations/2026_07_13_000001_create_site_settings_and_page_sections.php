<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Réglages du site (clé/valeur) : identité, coordonnées, réseaux
        // sociaux, bandeau d'annonce, SEO par page… Seules les clés
        // effectivement modifiées par l'admin existent en base ; le frontend
        // garde ses valeurs par défaut pour le reste.
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // Sections des pages vitrine : contenu texte éditable + visibilité.
        // Une ligne n'existe que si l'admin a modifié (ou masqué) la section.
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page', 60);
            $table->string('section', 60);
            $table->json('content')->nullable();
            $table->boolean('visible')->default(true);
            $table->timestamps();
            $table->unique(['page', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('site_settings');
    }
};
