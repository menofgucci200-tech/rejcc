<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fiche professionnelle du membre dans un groupe sectoriel : services
     * proposés, zone d'intervention, disponibilités et téléphone (affiché
     * aux membres du groupe sur choix explicite).
     */
    public function up(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->json('services')->nullable();
            $table->string('zone', 255)->nullable();
            $table->string('disponibilites', 255)->nullable();
            $table->boolean('telephone_visible')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('group_user', fn (Blueprint $table) => $table->dropColumn(['services', 'zone', 'disponibilites', 'telephone_visible']));
    }
};
