<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Communauté, lot 2 : sondages, cartes de partage (événement, offre,
 * formation, projet) et messages épinglés par les référents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_messages', function (Blueprint $table) {
            // Carte d'un élément de la plateforme (type = carte).
            $table->string('carte_type', 20)->nullable()->after('duree'); // evenement | offre | formation | projet
            $table->unsignedBigInteger('carte_id')->nullable()->after('carte_type');
            // Message épinglé en haut du salon.
            $table->timestamp('epingle_at')->nullable()->after('carte_id');
            $table->foreignId('epingle_par')->nullable()->after('epingle_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('salon_sondages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_message_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('question', 300);
            $table->boolean('choix_multiple')->default(false);
            $table->boolean('anonyme')->default(false);
            $table->timestamp('fin_at')->nullable();
            $table->timestamp('clos_at')->nullable();
            $table->timestamps();
        });

        Schema::create('salon_sondage_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_sondage_id')->constrained()->cascadeOnDelete();
            $table->string('libelle', 120);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('salon_sondage_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_sondage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salon_sondage_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['salon_sondage_option_id', 'user_id']);
            $table->index(['salon_sondage_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_sondage_votes');
        Schema::dropIfExists('salon_sondage_options');
        Schema::dropIfExists('salon_sondages');
        Schema::table('salon_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('epingle_par');
            $table->dropColumn(['carte_type', 'carte_id', 'epingle_at']);
        });
    }
};
