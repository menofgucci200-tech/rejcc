<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Documents : catégories gérées, fichier privé (servi par la plateforme
 * après contrôle d'accès) ou lien externe, type et taille calculés, accès
 * par document (tous les membres, abonnés, mentors, un groupe), propositions
 * des membres validées par l'équipe, vues et téléchargements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 80)->unique();
            $table->string('icone', 40)->default('folder-open');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')->constrained('document_categories')->nullOnDelete();
            $table->string('url')->nullable()->change();
            $table->string('fichier', 500)->nullable()->after('url');   // chemin privé (frontend)
            $table->string('fichier_nom', 200)->nullable()->after('fichier');
            $table->string('mime', 120)->nullable()->after('fichier_nom');
            $table->unsignedBigInteger('octets')->nullable()->after('mime');
            $table->string('acces', 12)->default('tous')->after('octets'); // tous | abonnes | mentors | groupe
            $table->foreignId('group_id')->nullable()->after('acces')->constrained()->nullOnDelete();
            $table->string('statut', 12)->default('publie')->index()->after('group_id'); // publie | en_attente | refuse
            $table->text('motif')->nullable()->after('statut');
            $table->foreignId('auteur_id')->nullable()->after('motif')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('vues')->default(0);
            $table->unsignedInteger('telechargements')->default(0);
            $table->timestamp('fichier_maj_at')->nullable();
            $table->timestamp('publie_at')->nullable();
        });

        $ordre = 0;
        foreach (DB::table('documents')->select('category')->distinct()->orderBy('category')->pluck('category') as $nom) {
            $id = DB::table('document_categories')->insertGetId(['nom' => $nom ?: 'Ressources', 'ordre' => ++$ordre, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('documents')->where('category', $nom)->update(['category_id' => $id]);
        }
        DB::table('documents')->update(['publie_at' => DB::raw('created_at'), 'fichier_maj_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('group_id');
            $table->dropConstrainedForeignId('auteur_id');
            $table->dropIndex(['statut']);
            $table->dropColumn(['fichier', 'fichier_nom', 'mime', 'octets', 'acces', 'statut', 'motif', 'vues', 'telechargements', 'fichier_maj_at', 'publie_at']);
        });
        Schema::dropIfExists('document_categories');
    }
};
