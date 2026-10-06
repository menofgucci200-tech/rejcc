<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Collaborer sur un projet, sur la plateforme : équipe (invitations et
 * demandes), membres qui suivent le projet, avancées publiées par l'équipe,
 * messages « À propos du projet ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 80)->nullable();       // ex. « Co-fondatrice », « Comptable »
            $table->string('statut', 12)->default('invite'); // invite | demande | membre
            $table->string('message', 500)->nullable();   // mot joint à la demande / l'invitation
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('project_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->string('image', 500)->nullable();
            $table->timestamps();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('listing_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
        Schema::dropIfExists('project_updates');
        Schema::dropIfExists('project_follows');
        Schema::dropIfExists('project_members');
    }
};
