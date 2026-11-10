<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CathoImpact : réponses au formulaire court du site (présélection des
 * parcours) et leur suivi par le département Communication, jusqu'à la
 * publication. Le texte du consentement accepté est conservé avec la réponse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cathoimpact_candidatures', function (Blueprint $table) {
            $table->id();
            $table->string('profil', 20);
            $table->string('nom_complet', 150);
            $table->string('email', 150);
            $table->string('whatsapp', 20);
            $table->string('entreprise', 150);
            $table->string('fonction', 150); // activité (entrepreneur) ou poste (professionnel)
            $table->string('secteur', 100);
            $table->string('ville', 100);
            $table->text('parcours');
            $table->text('inspiration');
            $table->string('lien', 500)->nullable();
            $table->string('photo')->nullable();
            $table->text('consentement_texte');
            $table->timestamp('consentement_at');

            // Suivi par la Communication.
            $table->string('statut', 30)->default('recu');
            $table->timestamp('statut_at')->nullable();
            $table->json('historique')->nullable();
            $table->foreignId('community_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('community_manager', 150)->nullable(); // texte libre (hors équipe)
            $table->string('lien_questionnaire', 500)->nullable();
            $table->string('lien_publication', 500)->nullable();
            $table->text('note_interne')->nullable();
            $table->timestamps();
            $table->index(['statut', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cathoimpact_candidatures');
    }
};
