<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Galerie photos de la vitrine (vie du réseau), gérée depuis
        // l'admin (Blocs de contenu → Galerie photos).
        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('caption', 200)->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_photos');
    }
};
