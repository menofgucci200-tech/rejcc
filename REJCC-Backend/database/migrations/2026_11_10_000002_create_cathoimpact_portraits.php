<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Galerie « Ils ont inspiré » de la page /cathoimpact : des cartes qui
 * renvoient vers la publication sur les réseaux sociaux (pas de page portrait
 * sur le site).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cathoimpact_portraits', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->string('accroche', 200)->nullable();
            $table->string('image_url', 500);
            $table->string('lien', 500);
            $table->string('reseau', 20);
            $table->foreignId('candidature_id')->nullable()->constrained('cathoimpact_candidatures')->nullOnDelete();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();
            $table->index(['visible', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cathoimpact_portraits');
    }
};
