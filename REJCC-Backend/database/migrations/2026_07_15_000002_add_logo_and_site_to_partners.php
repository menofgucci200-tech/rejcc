<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            // Logo du partenaire (URL du fichier uploadé) et lien vers son site.
            // Les deux sont optionnels : sans logo on affiche le nom, sans site
            // le logo n'est pas cliquable.
            $table->string('logo', 500)->nullable()->after('initials');
            $table->string('site_url', 500)->nullable()->after('logo');
            $table->string('sector')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn(['logo', 'site_url']);
        });
    }
};
