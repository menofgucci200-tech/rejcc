<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calendrier éditorial du département Communication : publications prévues
 * (sujet, type, format, canaux, responsable, statut et liens une fois
 * publiées) et tours de rôle par blocs de jours. Les colonnes rappel_*
 * marquent les e-mails déjà envoyés (pas de doublon).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_publications', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('heure', 5)->nullable(); // « HH:MM », facultative
            $table->string('titre', 200);
            $table->string('type', 30)->default('autre');
            $table->string('format', 60)->nullable(); // style libre : vidéo courte, carrousel…
            $table->json('canaux')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('responsable', 150)->nullable(); // nom libre (hors équipe)
            $table->text('notes')->nullable();
            $table->string('statut', 20)->default('prevu');
            $table->json('liens')->nullable();
            $table->timestamp('publie_at')->nullable();
            $table->foreignId('publie_par')->nullable()->constrained('users')->nullOnDelete();
            // Demande d'un autre département planifiée ici (sans clé étrangère : table gérée ailleurs).
            $table->unsignedBigInteger('demande_id')->nullable()->index();
            $table->timestamp('rappel_veille_at')->nullable();
            $table->timestamp('rappel_jour_at')->nullable();
            $table->timestamp('retard_signale_at')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['date', 'statut']);
        });

        Schema::create('communication_tours', function (Blueprint $table) {
            $table->id();
            $table->date('du');
            $table->date('au');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('responsable', 150)->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['du', 'au']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_tours');
        Schema::dropIfExists('communication_publications');
    }
};
