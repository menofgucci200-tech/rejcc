<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Emploi & Stage : circuit de validation (en attente → à corriger / publiée /
 * refusée, puis pourvue / clôturée), expiration, fiche d'offre complète
 * (contrat, secteur, télétravail, rémunération, début, durée, missions,
 * profil, compétences). Le type « Annonce » (doublon de la Marketplace)
 * devient « Mission ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('statut', 20)->default('en_attente')->index()->after('type');
            $table->text('motif')->nullable()->after('statut');
            $table->string('contrat', 20)->nullable()->after('motif');       // cdi | cdd | interim (emplois)
            $table->foreignId('group_id')->nullable()->after('author_id')->constrained()->nullOnDelete();
            $table->string('teletravail', 20)->default('sur_site')->after('lieu'); // sur_site | hybride | distance
            $table->string('remuneration', 120)->nullable()->after('teletravail');
            $table->date('debut')->nullable()->after('remuneration');
            $table->string('duree', 60)->nullable()->after('debut');
            $table->text('missions')->nullable()->after('description');
            $table->text('profil')->nullable()->after('missions');
            $table->json('competences')->nullable()->after('profil');
            $table->unsignedInteger('vues')->default(0);
            $table->date('expire_le')->nullable()->index();
            $table->timestamp('rappel_at')->nullable();
            $table->timestamp('publie_at')->nullable();
            $table->timestamp('decide_at')->nullable();
        });

        // Les offres déjà en ligne restent publiées ; les « annonces » deviennent des missions.
        foreach (DB::table('opportunities')->get() as $o) {
            $type = strtolower((string) $o->type);
            $type = in_array($type, ['emploi', 'stage', 'alternance', 'freelance', 'mission'], true) ? $type : 'mission';
            $expire = $o->deadline ?: now()->addDays(60)->toDateString();
            DB::table('opportunities')->where('id', $o->id)->update([
                'type' => $type, 'statut' => 'publiee', 'publie_at' => $o->created_at, 'decide_at' => $o->created_at,
                'expire_le' => $expire, 'contrat' => $type === 'emploi' ? 'cdi' : null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropIndex(['statut']);
            $table->dropIndex(['expire_le']);
            $table->dropColumn(['statut', 'motif', 'contrat', 'teletravail', 'remuneration', 'debut', 'duree', 'missions', 'profil', 'competences', 'vues', 'expire_le', 'rappel_at', 'publie_at', 'decide_at']);
        });
    }
};
