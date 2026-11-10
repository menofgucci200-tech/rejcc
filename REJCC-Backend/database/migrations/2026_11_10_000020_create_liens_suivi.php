<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liens de suivi du département Communication (rejcc.site/l/{code}) : chaque
 * lien renvoie vers une adresse enregistrée par la Com et compte ses clics
 * par jour, pour savoir quel canal (WhatsApp, Facebook…) ramène du monde.
 * Aucune donnée sur la personne qui clique n'est conservée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liens_suivi', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('cible', 2000);
            $table->string('libelle', 150);
            $table->string('canal', 20);
            $table->boolean('actif')->default(true);
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('liens_suivi_clics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lien_id')->constrained('liens_suivi')->cascadeOnDelete();
            $table->date('jour');
            $table->unsignedInteger('clics')->default(0);
            $table->unique(['lien_id', 'jour']);
            $table->index('jour');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liens_suivi_clics');
        Schema::dropIfExists('liens_suivi');
    }
};
