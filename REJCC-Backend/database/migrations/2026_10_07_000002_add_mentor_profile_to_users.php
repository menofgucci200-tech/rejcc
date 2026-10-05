<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Profil de mentor : expertises, présentation, disponibilités, format, capacité d'accueil. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('mentor_expertises')->nullable();
            $table->text('mentor_bio')->nullable();
            $table->string('mentor_disponibilites', 255)->nullable();
            $table->string('mentor_format', 20)->nullable(); // visio | presentiel | les_deux
            $table->unsignedTinyInteger('mentor_capacite')->default(3);
            $table->boolean('mentor_accepte')->default(true); // accepte de nouveaux mentorés
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mentor_expertises', 'mentor_bio', 'mentor_disponibilites', 'mentor_format', 'mentor_capacite', 'mentor_accepte']);
        });
    }
};
