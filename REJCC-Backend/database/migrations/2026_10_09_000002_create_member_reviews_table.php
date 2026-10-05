<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avis des membres sur un professionnel du réseau (note sur 5 et
     * commentaire) : un avis par membre et par professionnel, modifiable ;
     * l'administration peut masquer un avis abusif.
     */
    public function up(): void
    {
        Schema::create('member_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->unsignedTinyInteger('note');
            $table->text('commentaire')->nullable();
            $table->boolean('masque')->default(false);
            $table->timestamps();
            $table->unique(['reviewer_id', 'reviewed_id']);
            $table->index(['reviewed_id', 'masque']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_reviews');
    }
};
