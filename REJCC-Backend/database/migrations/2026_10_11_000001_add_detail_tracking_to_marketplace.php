<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace : compteurs de vues et de contacts, annonce rattachée à un
 * message (« À propos de votre annonce… ») et signalements d'annonces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->unsignedInteger('vues')->default(0)->after('reject_reason');
            $table->unsignedInteger('contacts')->default(0)->after('vues');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('listing_id')->nullable()->after('body')->constrained('marketplace_listings')->nullOnDelete();
        });

        Schema::create('listing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('motif', 500)->nullable();
            $table->string('statut', 20)->default('nouveau'); // nouveau | traite
            $table->timestamps();
            $table->index(['statut', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_reports');
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('listing_id');
        });
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->dropColumn(['vues', 'contacts']);
        });
    }
};
