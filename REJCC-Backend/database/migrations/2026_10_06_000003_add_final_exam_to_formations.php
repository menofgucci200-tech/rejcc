<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Examen final de certification, passé sur la plateforme. */
    public function up(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->json('examen')->nullable()->after('seuil_reussite'); // [{question, choix, bonne}]
        });
        Schema::table('formation_enrollments', function (Blueprint $table) {
            $table->unsignedTinyInteger('examen_score')->nullable();
            $table->timestamp('examen_reussi_at')->nullable();
            $table->unsignedSmallInteger('examen_echecs')->default(0);   // échecs consécutifs
            $table->timestamp('examen_bloque_jusqu')->nullable();        // pause après 3 échecs
        });
    }

    public function down(): void
    {
        Schema::table('formations', fn (Blueprint $table) => $table->dropColumn('examen'));
        Schema::table('formation_enrollments', fn (Blueprint $table) => $table->dropColumn(['examen_score', 'examen_reussi_at', 'examen_echecs', 'examen_bloque_jusqu']));
    }
};
