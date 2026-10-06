<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Emploi & Stage : offres sauvegardées et alertes (notification à la publication). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_favoris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['opportunity_id', 'user_id']);
        });

        Schema::create('job_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->nullable();
            $table->foreignId('group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('ville', 80)->nullable();
            $table->string('q', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_alerts');
        Schema::dropIfExists('opportunity_favoris');
    }
};
