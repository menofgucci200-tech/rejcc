<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Événements : fin, statut (brouillon, publié, annulé), réservation aux
 * abonnés, événement en ligne (lien de visio), ouverture et date limite
 * des inscriptions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->string('statut', 20)->default('publie')->after('category'); // brouillon | publie | annule
            $table->string('motif_annulation', 300)->nullable()->after('statut');
            $table->boolean('reserve_abonnes')->default(false)->after('capacity');
            $table->boolean('en_ligne')->default(false)->after('location');
            $table->string('lien_visio', 500)->nullable()->after('en_ligne');
            $table->boolean('inscriptions_ouvertes')->default(true)->after('reserve_abonnes');
            $table->dateTime('date_limite')->nullable()->after('inscriptions_ouvertes');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['ends_at', 'statut', 'motif_annulation', 'reserve_abonnes', 'en_ligne', 'lien_visio', 'inscriptions_ouvertes', 'date_limite']);
        });
    }
};
