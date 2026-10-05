<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Groupes sectoriels : identité visuelle (icône, couleur), lien du groupe
 * WhatsApp (réservé aux membres abonnés du groupe), référent désigné par
 * l'administration et annonce épinglée.
 */
return new class extends Migration
{
    private const IDENTITES = [
        'agriculture-peche' => ['sprout', '#2E8B57'],
        'informatique-technologie' => ['cpu', '#4F6FBF'],
        'communication-medias' => ['megaphone', '#C2185B'],
        'finance-investissement' => ['landmark', '#031D59'],
        'administration-gestion' => ['folder-open', '#5B677A'],
        'education-formation' => ['graduation-cap', '#7B5EA7'],
        'sante-bien-etre' => ['heart-pulse', '#D64545'],
        'btp-construction' => ['building-2', '#E07B24'],
        'industrie-production' => ['factory', '#6B7A8F'],
        'commerce-distribution' => ['shopping-bag', '#0E8A96'],
        'transport-logistique' => ['truck', '#3A6EA5'],
        'hotellerie-tourisme' => ['bed-double', '#B7790F'],
        'artisanat-creation' => ['scissors', '#A0522D'],
        'evenementiel-loisirs' => ['party-popper', '#D1468C'],
        'developpement-durable-environnement' => ['leaf', '#22A85A'],
        'action-sociale-solidarite' => ['hand-heart', '#AC0100'],
    ];

    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('icone', 40)->nullable()->after('description');
            $table->string('couleur', 7)->nullable()->after('icone');
            $table->string('whatsapp_url')->nullable()->after('couleur');
            $table->foreignId('referent_id')->nullable()->after('whatsapp_url')->constrained('users')->nullOnDelete();
            $table->text('annonce')->nullable()->after('referent_id');
            $table->timestamp('annonce_at')->nullable()->after('annonce');
        });

        foreach (self::IDENTITES as $slug => [$icone, $couleur]) {
            DB::table('groups')->where('slug', $slug)->update(['icone' => $icone, 'couleur' => $couleur]);
        }
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referent_id');
            $table->dropColumn(['icone', 'couleur', 'whatsapp_url', 'annonce', 'annonce_at']);
        });
    }
};
