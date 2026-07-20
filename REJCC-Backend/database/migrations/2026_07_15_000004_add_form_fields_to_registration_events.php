<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_events', function (Blueprint $table) {
            // Affiche de l'événement (URL de l'image uploadée) et définitions
            // des champs personnalisés du formulaire d'inscription.
            $table->string('poster', 500)->nullable()->after('description');
            $table->json('fields')->nullable()->after('poster');
        });

        Schema::table('event_participants', function (Blueprint $table) {
            // Réponses aux champs personnalisés : { clé_champ => valeur }.
            $table->json('answers')->nullable()->after('is_member');
        });
    }

    public function down(): void
    {
        Schema::table('registration_events', function (Blueprint $table) {
            $table->dropColumn(['poster', 'fields']);
        });
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropColumn('answers');
        });
    }
};
