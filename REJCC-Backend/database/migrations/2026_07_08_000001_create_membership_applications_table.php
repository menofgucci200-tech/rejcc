<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();

            // Informations générales
            $table->string('nom_prenoms');
            $table->string('sexe');
            $table->string('tranche_age');
            $table->string('whatsapp', 30);
            $table->string('email');
            $table->string('connotation_religieuse');
            $table->string('paroisse')->nullable();

            // Profil
            $table->json('statut_actuel');
            $table->string('niveau_etudes');
            $table->string('domaines_formation');

            // Compétences
            $table->json('competences');
            $table->text('description_competences')->nullable();

            // Entrepreneuriat
            $table->string('a_activite');
            $table->string('nom_activite')->nullable();
            $table->json('secteurs_activite')->nullable();
            $table->string('anciennete')->nullable();
            $table->json('domaines_futurs')->nullable();

            // Attentes et profil
            $table->json('attentes');
            $table->json('formations_interet');
            $table->string('defi_principal');
            $table->string('revenu_mensuel');

            $table->boolean('traite')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_applications');
    }
};
