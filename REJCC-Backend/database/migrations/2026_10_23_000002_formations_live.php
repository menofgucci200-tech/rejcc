<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions live des formations : une session est un événement en ligne
 * (calendrier, inscriptions, rappels des membres) rattaché à une formation,
 * avec son animateur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('formation_id')->nullable()->after('category')->constrained('formations')->nullOnDelete();
            $table->string('animateur', 120)->nullable()->after('formation_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('formation_id');
            $table->dropColumn('animateur');
        });
    }
};
