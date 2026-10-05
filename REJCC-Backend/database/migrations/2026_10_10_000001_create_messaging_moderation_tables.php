<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messagerie : blocage d'un membre, conversations archivées (de son côté
 * seulement) et signalements de conversations à l'administration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'blocked_id']);
        });

        Schema::create('conversation_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('other_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('archived_at');
            $table->unique(['user_id', 'other_id']);
        });

        Schema::create('message_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reported_id')->constrained('users')->cascadeOnDelete();
            $table->string('motif', 500)->nullable();
            $table->string('statut', 20)->default('nouveau'); // nouveau | traite
            $table->string('decision', 20)->nullable(); // classe | averti
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_at')->nullable();
            $table->timestamps();
            $table->index(['statut', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reports');
        Schema::dropIfExists('conversation_archives');
        Schema::dropIfExists('message_blocks');
    }
};
