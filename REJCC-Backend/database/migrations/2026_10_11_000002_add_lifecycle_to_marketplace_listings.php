<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace : cycle de vie d'une annonce. Une annonce en ligne expire
 * 90 jours après sa publication (rappel 7 jours avant, renouvellement en un
 * clic) ; le vendeur peut la marquer « Vendu / indisponible ».
 * Statuts : en_attente | approuve | refuse | indisponible | expiree.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->timestamp('publie_le')->nullable()->after('statut');
            $table->timestamp('expire_le')->nullable()->after('publie_le');
            $table->timestamp('rappel_expiration_at')->nullable()->after('expire_le');
        });

        // Annonces déjà en ligne : 90 jours à compter d'aujourd'hui.
        DB::table('marketplace_listings')->where('statut', 'approuve')
            ->update(['publie_le' => DB::raw('updated_at'), 'expire_le' => now()->addDays(90)]);
    }

    public function down(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->dropColumn(['publie_le', 'expire_le', 'rappel_expiration_at']);
        });
    }
};
