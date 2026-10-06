<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Albums de la galerie (une sortie, une assemblée, une messe…) : page
        // « Galerie » du site vitrine. Les photos sans album restent possibles.
        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 160);
            $table->string('slug', 180)->unique();
            $table->date('date_evenement')->nullable();
            $table->string('lieu', 160)->nullable();
            $table->text('description')->nullable();
            $table->string('couverture', 500)->nullable();
            $table->boolean('publie')->default(true);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->foreignId('album_id')->nullable()->after('id')->constrained('gallery_albums')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('album_id');
        });
        Schema::dropIfExists('gallery_albums');
    }
};
