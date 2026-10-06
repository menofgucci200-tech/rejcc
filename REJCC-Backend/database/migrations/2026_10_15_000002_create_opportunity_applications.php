<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidatures sur la plateforme : le membre postule (message + CV), le
 * recruteur les classe (présélection, retenue, non retenue), chaque étape
 * est notifiée. Messages « À propos de l'offre » dans la messagerie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->string('cv_url', 500)->nullable();
            $table->string('cv_name', 200)->nullable();
            $table->string('statut', 20)->default('recue'); // recue | preselection | retenue | non_retenue | retiree
            $table->text('note')->nullable();               // note privée du recruteur
            $table->timestamp('vue_at')->nullable();        // ouverte par le recruteur
            $table->timestamps();
            $table->unique(['opportunity_id', 'user_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('opportunity_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opportunity_id');
        });
        Schema::dropIfExists('opportunity_applications');
    }
};
