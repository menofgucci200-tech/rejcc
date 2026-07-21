<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_events', function (Blueprint $table) {
            // Date limite d'inscription (optionnelle) : passé cette date, les
            // inscriptions se ferment automatiquement.
            $table->dateTime('registration_deadline')->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('registration_events', function (Blueprint $table) {
            $table->dropColumn('registration_deadline');
        });
    }
};
