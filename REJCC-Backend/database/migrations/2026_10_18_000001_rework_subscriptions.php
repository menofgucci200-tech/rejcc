<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abonnement annuel : bénéficiaire (abonnement offert par un autre membre),
 * moyen de paiement réel, période couverte, reçu numéroté, et suivi des
 * rappels d'échéance envoyés au membre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('beneficiaire_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('moyen', 40)->nullable()->after('provider');   // Wave, Orange Money, MTN, Moov, carte…
            $table->timestamp('paye_at')->nullable();
            $table->date('periode_debut')->nullable();
            $table->date('periode_fin')->nullable();
            $table->string('recu_numero', 30)->nullable()->unique();
            $table->string('recu_fichier', 255)->nullable();
            $table->timestamp('verifie_at')->nullable();                 // dernière interrogation de CinetPay
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('abonnement_rappel', 40)->nullable();          // dernier rappel envoyé (échéance:étape)
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('abonnement_rappel'));
        Schema::table('payments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('beneficiaire_id');
            $t->dropColumn(['moyen', 'paye_at', 'periode_debut', 'periode_fin', 'recu_numero', 'recu_fichier', 'verifie_at']);
        });
    }
};
