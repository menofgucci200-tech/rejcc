<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor')->nullable();   // nom + email au moment de l'action
            $table->string('action', 60);          // Création / Modification / Suppression / …
            $table->string('target')->nullable();  // ex. « Formation #5 »
            $table->string('method', 10);
            $table->string('path', 200);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
