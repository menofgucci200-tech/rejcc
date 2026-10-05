<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Avis signalé par un membre (abusif, faux…) : à examiner par l'administration. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_reviews', function (Blueprint $table) {
            $table->timestamp('signale_at')->nullable()->after('masque');
            $table->foreignId('signale_par')->nullable()->after('signale_at')->constrained('users')->nullOnDelete();
            $table->string('motif_signalement', 300)->nullable()->after('signale_par');
        });
    }

    public function down(): void
    {
        Schema::table('member_reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signale_par');
            $table->dropColumn(['signale_at', 'motif_signalement']);
        });
    }
};
