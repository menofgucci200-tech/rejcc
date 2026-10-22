<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Département du Bureau exécutif d'un compte du dashboard (Trésorerie,
 * Juridique…). Distinct de `departement`, réservé aux comptes de test.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bureau_departement', 40)->nullable()->after('departement')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['bureau_departement']);
            $table->dropColumn('bureau_departement');
        });
    }
};
