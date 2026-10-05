<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relation de mentorat entre un membre (mentoré) et un mentor :
     * demande → acceptée / refusée / annulée → terminée.
     */
    public function up(): void
    {
        Schema::create('mentorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentore_id')->constrained('users')->cascadeOnDelete();
            $table->string('statut', 20)->default('en_attente'); // en_attente | accepte | refuse | annule | termine
            $table->string('objectif', 200);
            $table->text('besoin')->nullable();
            $table->text('reponse')->nullable(); // mot du mentor à l'acceptation ou au refus
            $table->timestamp('repondu_at')->nullable();
            $table->timestamp('termine_at')->nullable();
            $table->timestamps();
            $table->index(['mentor_id', 'statut']);
            $table->index(['mentore_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentorships');
    }
};
