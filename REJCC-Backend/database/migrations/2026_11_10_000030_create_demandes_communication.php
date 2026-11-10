<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes de communication : les départements du Bureau demandent à la
 * Communication un visuel ou une annonce depuis le dashboard (au lieu de
 * WhatsApp). Seule la Communication les traite : nouvelle → acceptée →
 * planifiée → publiée, ou refusée (avec motif), ou annulée par le demandeur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_communication', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demandeur_id')->constrained('users')->cascadeOnDelete();
            $table->string('departement', 40)->nullable(); // slug du département ; null = Administration
            $table->string('type', 40);
            $table->string('titre', 150);
            $table->text('message');
            $table->date('date_souhaitee');
            $table->date('date_evenement')->nullable();
            $table->json('canaux');
            $table->json('fichiers')->nullable(); // [{chemin, nom, mime, octets}] dans le stockage privé partagé
            $table->string('contact', 150);

            // Traitement par la Communication.
            $table->string('statut', 20)->default('nouvelle');
            $table->timestamp('statut_at')->nullable();
            $table->json('historique')->nullable(); // statuts et commentaires, horodatés
            $table->text('motif_refus')->nullable();
            $table->date('date_publication_prevue')->nullable();
            $table->unsignedBigInteger('publication_id')->nullable(); // publication du calendrier éditorial
            $table->string('lien_publication', 500)->nullable();
            $table->timestamps();
            $table->index(['statut', 'date_souhaitee']);
            $table->index('departement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_communication');
    }
};
