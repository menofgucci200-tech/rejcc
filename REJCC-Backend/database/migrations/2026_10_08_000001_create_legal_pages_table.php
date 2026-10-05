<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pages légales du site (mentions, CGU, confidentialité…), rédigées
     * depuis l'administration. Créées vides : le site affiche « en cours de
     * rédaction » tant qu'une page n'est pas publiée.
     */
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('titre', 150);
            $table->string('resume', 300)->nullable();
            $table->longText('contenu')->nullable(); // Markdown
            $table->boolean('publie')->default(false);
            $table->string('version', 20)->nullable(); // ex. 1.0, incrémentée à chaque publication
            $table->timestamp('publie_at')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });

        $pages = [
            ['mentions-legales', 'Mentions légales', 'Éditeur du site, hébergeur et informations sur le REJCC.'],
            ['cgu', "Conditions générales d'utilisation", "Règles d'utilisation du site et de l'espace membre."],
            ['politique-de-confidentialite', 'Politique de confidentialité', 'Données personnelles collectées, usages, durées de conservation et droits des membres.'],
            ['politique-cookies', 'Politique cookies', 'Cookies et traceurs utilisés sur le site.'],
            ['charte-du-membre', 'Charte du membre', 'Valeurs et règles de conduite au sein du réseau.'],
            ['conditions-abonnement', "Conditions d'abonnement et de paiement", "Cotisation annuelle, renouvellement, paiement et remboursement."],
        ];
        foreach ($pages as $i => [$slug, $titre, $resume]) {
            DB::table('legal_pages')->insert([
                'slug' => $slug, 'titre' => $titre, 'resume' => $resume, 'ordre' => $i + 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
    }
};
