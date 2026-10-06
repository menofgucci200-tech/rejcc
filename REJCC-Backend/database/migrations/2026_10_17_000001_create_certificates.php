<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registre officiel des certificats et attestations délivrés par le REJCC.
 * Le document imprimé ne fait pas foi : seul ce registre, consultable sur la
 * page publique de vérification, atteste de l'authenticité d'un certificat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 12);                    // formation | evenement | parcours
            $table->unsignedBigInteger('source_id');      // inscription formation / inscription événement / badge parcours
            $table->string('reference', 40)->unique();    // lisible : REJCC-CERT-2026-0042
            $table->string('code', 12)->unique();         // vérification : aléatoire, impossible à deviner
            $table->string('nom', 160);                   // nom du titulaire, figé à la délivrance
            $table->string('email', 190)->nullable();     // invités d'un événement (sans compte)
            $table->string('intitule', 80);               // « Certificat de réussite »…
            $table->string('titre', 255);                 // formation, événement ou parcours
            $table->json('details')->nullable();          // durée, score, compétences, lieu…
            $table->json('signataires')->nullable();      // instantané au moment de la délivrance
            $table->string('lieu', 80)->default('Abidjan');
            $table->date('delivre_le');
            $table->string('statut', 12)->default('valide')->index(); // valide | revoque
            $table->timestamp('revoque_at')->nullable();
            $table->text('motif_revocation')->nullable();
            $table->foreignId('remplace_par_id')->nullable()->constrained('certificates')->nullOnDelete();
            $table->string('fichier', 255)->nullable();   // PDF officiel conservé (privé)
            $table->string('empreinte', 64)->nullable()->index(); // SHA-256 du PDF délivré
            $table->boolean('signe_electroniquement')->default(false);
            $table->boolean('visible_bio')->default(false);
            $table->text('correction_demandee')->nullable();
            $table->timestamp('correction_demandee_at')->nullable();
            $table->unsignedInteger('verifications')->default(0);
            $table->timestamp('derniere_verification_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'source_id']);
            $table->index(['user_id', 'statut']);
        });

        Schema::create('certificate_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('methode', 12);   // code | qr | fichier
            $table->string('resultat', 16);  // valide | revoque | inconnu | intact | modifie
            $table->string('ip_hash', 64)->nullable();
            $table->string('agent', 160)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('formations', function (Blueprint $table) {
            $table->json('competences')->nullable();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('attestation')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn('attestation'));
        Schema::table('formations', fn (Blueprint $t) => $t->dropColumn('competences'));
        Schema::dropIfExists('certificate_verifications');
        Schema::dropIfExists('certificates');
    }
};
