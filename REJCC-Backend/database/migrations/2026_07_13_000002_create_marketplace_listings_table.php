<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Marketplace : services et produits proposés par les membres,
        // soumis à validation de l'administration avant publication.
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('service'); // service | produit
            $table->string('title', 120);
            $table->string('category', 60);
            $table->text('description');
            $table->string('price', 80)->nullable(); // texte libre : « 15 000 FCFA », « Sur devis »…
            $table->string('contact', 60)->nullable(); // téléphone / WhatsApp
            $table->string('photo', 500)->nullable();
            $table->string('statut', 20)->default('en_attente'); // en_attente | approuve | refuse
            $table->string('reject_reason', 300)->nullable();
            $table->timestamps();
            $table->index(['statut', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
