<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Champs de la page biographique publique (ouverte par le QR code de la
     * carte membre), renseignés par le membre dans ses Paramètres.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('titre', 120)->nullable()->after('organisation');   // fonction / titre professionnel
            $table->string('diocese', 120)->nullable()->after('paroisse');
            $table->json('competences')->nullable()->after('bio');            // ["Comptabilité", …]
            $table->json('parcours')->nullable()->after('competences');        // [{periode, titre, structure}, …]
            $table->json('liens')->nullable()->after('parcours');              // {site, linkedin, facebook, instagram}
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['titre', 'diocese', 'competences', 'parcours', 'liens']);
        });
    }
};
