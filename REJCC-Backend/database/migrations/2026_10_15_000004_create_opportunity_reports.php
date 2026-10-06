<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Signalements d'offres d'emploi par les membres (offre frauduleuse, inappropriée…). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('motif', 500);
            $table->string('statut', 12)->default('nouveau'); // nouveau | classe
            $table->timestamps();
            $table->unique(['opportunity_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_reports');
    }
};
