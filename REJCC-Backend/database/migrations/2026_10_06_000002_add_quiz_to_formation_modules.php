<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Quiz de validation des modules (QCM corrigé côté serveur) et seuil de réussite. */
    public function up(): void
    {
        Schema::table('formation_modules', function (Blueprint $table) {
            $table->json('quiz')->nullable()->after('ressources'); // [{question, choix: [...], bonne: index}]
        });
        Schema::table('formation_module_completions', function (Blueprint $table) {
            $table->unsignedTinyInteger('quiz_score')->nullable(); // % obtenu au quiz
        });
        Schema::table('formations', function (Blueprint $table) {
            $table->unsignedTinyInteger('seuil_reussite')->default(70); // % minimum aux quiz et à l'examen
        });
    }

    public function down(): void
    {
        Schema::table('formation_modules', fn (Blueprint $table) => $table->dropColumn('quiz'));
        Schema::table('formation_module_completions', fn (Blueprint $table) => $table->dropColumn('quiz_score'));
        Schema::table('formations', fn (Blueprint $table) => $table->dropColumn('seuil_reussite'));
    }
};
