<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Candidatures « Devenir mentor » déposées par les membres, validées par l'administration. */
    public function up(): void
    {
        Schema::create('mentor_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('expertises');
            $table->text('experience');
            $table->text('motivation');
            $table->string('disponibilites', 255)->nullable();
            $table->string('statut', 20)->default('en_attente'); // en_attente | acceptee | refusee
            $table->text('reponse')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_applications');
    }
};
