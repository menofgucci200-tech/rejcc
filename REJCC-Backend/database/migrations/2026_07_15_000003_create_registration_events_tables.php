<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Événements à inscription publique (via QR code) : lancement du
        // 10/10/2026 et tous les événements à venir. Distinct des « events »
        // vitrine (où seuls les membres connectés s'inscrivent).
        Schema::create('registration_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->unsignedInteger('capacity')->nullable(); // null = places illimitées
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });

        // Un participant inscrit à un événement (public : pas forcément membre).
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_event_id')->constrained()->cascadeOnDelete();
            $table->string('prenom');
            $table->string('nom');
            $table->string('telephone', 30);
            $table->string('email')->nullable();
            $table->boolean('is_member')->default(false);
            $table->timestamps();
            // Une même personne (par téléphone) ne s'inscrit qu'une fois par événement.
            $table->unique(['registration_event_id', 'telephone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('registration_events');
    }
};
