<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Contenu suivi sur la plateforme : leçon rédigée et ressources téléchargeables. */
    public function up(): void
    {
        Schema::table('formation_modules', function (Blueprint $table) {
            $table->longText('contenu')->nullable()->after('description');   // leçon (Markdown simple)
            $table->json('ressources')->nullable()->after('document_url');   // [{nom, url, taille}]
        });
    }

    public function down(): void
    {
        Schema::table('formation_modules', function (Blueprint $table) {
            $table->dropColumn(['contenu', 'ressources']);
        });
    }
};
