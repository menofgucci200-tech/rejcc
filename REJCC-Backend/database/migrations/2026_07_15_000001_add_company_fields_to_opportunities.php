<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            // Détails « offre d'emploi / stage » : entreprise, site web, lieu.
            // (media_url / media_name existent déjà, ajoutés pour tous les
            //  contenus par 2026_07_12_000001_add_media_to_content_tables.)
            $table->string('entreprise')->nullable()->after('type');
            $table->string('site_url', 500)->nullable()->after('entreprise');
            $table->string('lieu')->nullable()->after('site_url');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn(['entreprise', 'site_url', 'lieu']);
        });
    }
};
