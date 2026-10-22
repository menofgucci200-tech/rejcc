<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jour J des sessions live : second rappel (une heure avant, avec le lien
 * direct) et clic « Rejoindre la visio » (présence probable, pour l'émargement).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->timestamp('rappel_h1_at')->nullable()->after('rappel_at');
            $table->timestamp('rejoint_at')->nullable()->after('rappel_h1_at');
        });

        // Sessions live existantes : le résumé répétait la description sur la fiche.
        \Illuminate\Support\Facades\DB::table('events')->where('category', 'Formation live')->update(['excerpt' => null]);
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['rappel_h1_at', 'rejoint_at']);
        });
    }
};
