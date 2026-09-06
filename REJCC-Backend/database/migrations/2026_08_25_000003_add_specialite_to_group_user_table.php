<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            // Description libre de l'activité/spécialité du membre dans CE groupe
            // (ex. « Plombier spécialisé en dépannage sanitaire et chauffage »),
            // saisie au moment de l'adhésion au groupe.
            $table->text('specialite')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropColumn('specialite');
        });
    }
};
