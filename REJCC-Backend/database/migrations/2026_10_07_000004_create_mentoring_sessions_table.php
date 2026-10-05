<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Séances d'un mentorat : proposée → confirmée → réalisée (compte rendu), ou annulée. */
    public function up(): void
    {
        Schema::create('mentoring_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentorship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('propose_par')->constrained('users')->cascadeOnDelete();
            $table->dateTime('debut_at');
            $table->unsignedSmallInteger('duree_minutes')->default(60);
            $table->string('format', 20)->default('visio'); // visio | presentiel
            $table->string('lieu', 255)->nullable(); // adresse ou lien de visio
            $table->text('ordre_du_jour')->nullable();
            $table->string('statut', 20)->default('proposee'); // proposee | confirmee | annulee | realisee
            $table->string('motif_annulation', 300)->nullable();
            $table->text('compte_rendu')->nullable();
            $table->text('prochaines_etapes')->nullable();
            $table->timestamps();
            $table->index(['mentorship_id', 'debut_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentoring_sessions');
    }
};
