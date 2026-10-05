<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consentement aux documents légaux lors de l'adhésion : date et versions
     * acceptées (CGU, confidentialité, charte), reportées sur le compte.
     */
    public function up(): void
    {
        foreach (['membership_applications', 'users'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->timestamp('conditions_acceptees_at')->nullable();
                $t->json('versions_acceptees')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['membership_applications', 'users'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn(['conditions_acceptees_at', 'versions_acceptees']));
        }
    }
};
