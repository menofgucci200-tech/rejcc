<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace : catégories alignées sur les 16 groupes sectoriels (une seule
 * classification dans tout le réseau), prix chiffré pour le tri et favoris.
 */
return new class extends Migration
{
    private const CORRESPONDANCES = [
        'Artisanat & BTP' => 'btp-construction',
        'Alimentation & Restauration' => 'hotellerie-tourisme',
        'Mode & Beauté' => 'artisanat-creation',
        'Services numériques' => 'informatique-technologie',
        'Transport & Logistique' => 'transport-logistique',
        'Éducation & Formation' => 'education-formation',
        'Santé & Bien-être' => 'sante-bien-etre',
        'Agriculture' => 'agriculture-peche',
        'Commerce & Distribution' => 'commerce-distribution',
        'Finance & Conseil' => 'finance-investissement',
        'Événementiel' => 'evenementiel-loisirs',
    ];

    public function up(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('category')->constrained('groups')->nullOnDelete();
            $table->unsignedBigInteger('prix_valeur')->nullable()->after('price');
        });

        Schema::create('marketplace_favoris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'listing_id']);
        });

        foreach (self::CORRESPONDANCES as $ancienne => $slug) {
            $groupe = DB::table('groups')->where('slug', $slug)->first();
            if ($groupe) {
                DB::table('marketplace_listings')->where('category', $ancienne)
                    ->update(['group_id' => $groupe->id, 'category' => $groupe->name]);
            }
        }

        foreach (DB::table('marketplace_listings')->whereNotNull('price')->get(['id', 'price']) as $l) {
            DB::table('marketplace_listings')->where('id', $l->id)
                ->update(['prix_valeur' => \App\Models\MarketplaceListing::valeurPrix($l->price)]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_favoris');
        Schema::table('marketplace_listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn('prix_valeur');
        });
    }
};
