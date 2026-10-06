<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Projets : circuit de validation (en évaluation → à compléter / validé /
 * refusé, motif transmis au porteur), stade du projet, fiche complète
 * (secteur, ville, visuel, problème/solution/public/impact, besoins).
 * Remplace l'ancienne colonne libre « status » (reste de l'Incubateur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('statut', 20)->default('evaluation')->index()->after('status');
            $table->string('stade', 20)->default('idee')->after('statut');
            $table->text('motif')->nullable()->after('stade'); // refus ou précisions demandées
            $table->foreignId('group_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('ville', 80)->nullable()->after('group_id');
            $table->string('image', 500)->nullable()->after('ville');
            $table->string('accroche', 200)->nullable()->after('title');
            $table->text('probleme')->nullable()->after('description');
            $table->text('solution')->nullable()->after('probleme');
            $table->text('cible')->nullable()->after('solution');
            $table->text('impact')->nullable()->after('cible');
            $table->json('besoins')->nullable()->after('impact');
            $table->string('lien', 500)->nullable()->after('besoins');
            $table->unsignedInteger('vues')->default(0)->after('lien');
            $table->timestamp('soumis_at')->nullable();
            $table->timestamp('decide_at')->nullable();
        });

        // Reprise des anciens statuts libres.
        $correspondance = [
            'En évaluation' => ['evaluation', 'idee', []],
            'Refusé' => ['refuse', 'idee', []],
            'En développement' => ['valide', 'developpement', []],
            'Recherche partenaires' => ['valide', 'developpement', ['partenaires']],
            'Financement en cours' => ['valide', 'developpement', ['financement']],
            'Lancé' => ['valide', 'lance', []],
            'Financé' => ['valide', 'lance', []],
        ];
        foreach (DB::table('projects')->get() as $p) {
            [$statut, $stade, $besoins] = $correspondance[$p->status] ?? ['valide', 'developpement', []];
            DB::table('projects')->where('id', $p->id)->update([
                'statut' => $statut, 'stade' => $stade, 'besoins' => json_encode($besoins),
                'soumis_at' => $p->created_at, 'decide_at' => $statut === 'evaluation' ? null : $p->updated_at,
            ]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status', 60)->default('En évaluation');
        });
        $retour = ['evaluation' => 'En évaluation', 'a_completer' => 'En évaluation', 'refuse' => 'Refusé', 'retire' => 'Refusé'];
        foreach (DB::table('projects')->get() as $p) {
            DB::table('projects')->where('id', $p->id)->update(['status' => $retour[$p->statut] ?? ($p->stade === 'lance' ? 'Lancé' : 'En développement')]);
        }
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropIndex(['statut']);
            $table->dropColumn(['statut', 'stade', 'motif', 'ville', 'image', 'accroche', 'probleme', 'solution', 'cible', 'impact', 'besoins', 'lien', 'vues', 'soumis_at', 'decide_at']);
        });
    }
};
