<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions live : public visé (inscrits à la formation, groupes sectoriels,
 * profils), formateurs (membre du réseau ou intervenant extérieur, avec un
 * lien personnel par session) et réunion Google Meet créée par le compte
 * Google du REJCC.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('cible_formation')->default(false)->after('animateur');
            $table->json('cible_groupes')->nullable()->after('cible_formation');
            $table->json('cible_profils')->nullable()->after('cible_groupes');
            $table->string('google_event_id', 255)->nullable()->after('lien_visio');
        });

        Schema::create('formateurs', function (Blueprint $table) {
            $table->id();
            // Membre du réseau qui donne la formation ; vide pour un intervenant extérieur.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('prenom', 80);
            $table->string('nom', 80);
            $table->string('email', 150)->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('specialite', 160)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('event_formateur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('formateur_id')->constrained()->cascadeOnDelete();
            // Lien personnel d'animateur pour cette session (sans compte ni mot de passe).
            $table->string('jeton', 64)->unique();
            $table->timestamps();
            $table->unique(['event_id', 'formateur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_formateur');
        Schema::dropIfExists('formateurs');
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['cible_formation', 'cible_groupes', 'cible_profils', 'google_event_id']);
        });
    }
};
