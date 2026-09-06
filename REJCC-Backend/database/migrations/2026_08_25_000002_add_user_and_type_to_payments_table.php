<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('member_id')->constrained()->nullOnDelete();
            // adhesion (candidature initiale, historique) / abonnement (accès annuel aux fonctionnalités premium)
            $table->string('type')->default('adhesion')->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('type');
        });
    }
};
