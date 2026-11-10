<?php

use App\Support\OutilsCommunication;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boîte à outils du département Communication (rubrique « communication ») :
 * documents de marque (fichiers privés, réservés à l'équipe), fichier des
 * contacts médias et bibliothèque de textes prêts à copier, pré-remplie avec
 * les textes par défaut (insertion idempotente, par clé).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_fichiers', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 150);
            $table->text('description')->nullable();
            $table->string('categorie', 30)->default('autres');
            $table->string('fichier')->nullable(); // chemin sur le disque privé partagé (marque/…)
            $table->string('fichier_nom', 255)->nullable();
            $table->string('lien', 500)->nullable(); // ou un lien (gabarit Canva, dossier partagé…)
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('octets')->nullable();
            $table->unsignedInteger('telechargements')->default(0);
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('categorie');
        });

        Schema::create('contacts_medias', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('media', 150)->nullable(); // média ou organisation
            $table->string('type', 30)->default('autre');
            $table->string('fonction', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('telephone', 40)->nullable(); // téléphone ou WhatsApp
            $table->string('ville', 100)->nullable();
            $table->text('notes')->nullable();
            $table->date('dernier_contact')->nullable();
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('type');
        });

        Schema::create('communication_textes', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 60)->nullable()->unique(); // textes par défaut seulement
            $table->string('categorie', 30);
            $table->string('titre', 150);
            $table->text('contenu');
            $table->unsignedSmallInteger('limite')->nullable(); // limite de caractères conseillée (bio…)
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['categorie', 'ordre']);
        });

        OutilsCommunication::installerTextesParDefaut();
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_textes');
        Schema::dropIfExists('contacts_medias');
        Schema::dropIfExists('communication_fichiers');
    }
};
