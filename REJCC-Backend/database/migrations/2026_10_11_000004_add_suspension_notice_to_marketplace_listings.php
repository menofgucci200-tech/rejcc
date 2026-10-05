<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Marketplace : date à laquelle le vendeur a été prévenu de la suspension de ses annonces (abonnement expiré). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->timestamp('suspension_notifiee_at')->nullable()->after('rappel_expiration_at');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->dropColumn('suspension_notifiee_at');
        });
    }
};
