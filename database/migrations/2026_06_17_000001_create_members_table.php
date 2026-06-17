<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('profil'); // etudiant / porteur / entrepreneur
            $table->string('prenom');
            $table->string('nom');
            $table->string('email');
            $table->string('telephone', 20);
            $table->string('genre')->nullable();
            $table->string('ville');
            $table->string('secteur');
            $table->string('organisation')->nullable();
            $table->text('message')->nullable();
            $table->string('paiement'); // wave / orange / djamo
            $table->string('statut')->default('pending'); // pending / active
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
