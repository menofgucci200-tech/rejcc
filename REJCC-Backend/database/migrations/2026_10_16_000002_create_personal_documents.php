<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coffre-fort personnel du membre : pièces d'identité, passeport, extrait
 * d'acte de naissance, diplômes… Fichiers privés (chiffrés par le site),
 * visibles du seul membre, sauf partage volontaire avec l'équipe REJCC.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);           // cni | passeport | extrait | casier | diplome | cv | attestation | autre
            $table->string('titre', 150)->nullable();
            $table->string('fichier', 500);       // chemin privé (frontend), dans le dossier du membre
            $table->string('fichier_nom', 200);
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('octets')->nullable();
            $table->date('delivre_le')->nullable();
            $table->date('expire_le')->nullable();
            $table->boolean('partage')->default(false);
            $table->timestamp('partage_at')->nullable();
            $table->timestamp('consulte_equipe_at')->nullable();
            $table->timestamp('rappel_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_documents');
    }
};
