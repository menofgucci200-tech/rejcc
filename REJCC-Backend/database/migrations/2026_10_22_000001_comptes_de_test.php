<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campagne de test sur le site réel : un compte de test porte le nom de son
 * département ; ses retours (bugs, suggestions) sont enregistrés avec la
 * page et le navigateur d'où ils partent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('departement', 120)->nullable()->after('role');
        });

        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('departement', 120)->nullable();
            $table->string('categorie', 30);
            $table->string('page', 500)->nullable();
            $table->string('navigateur', 255)->nullable();
            $table->text('message');
            $table->string('statut', 20)->default('nouvelle');
            $table->text('reponse')->nullable();
            $table->timestamps();

            $table->index(['departement', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('departement');
        });
    }
};
