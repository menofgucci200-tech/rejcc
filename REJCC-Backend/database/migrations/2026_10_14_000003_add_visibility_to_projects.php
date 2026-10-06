<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projets : accord du porteur pour l'affichage sur le site public, et mise
 * « à la une » par l'équipe (en tête de liste et sur la vitrine).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('public_ok')->default(false)->after('lien');
            $table->boolean('a_la_une')->default(false)->after('public_ok');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['public_ok', 'a_la_une']);
        });
    }
};
