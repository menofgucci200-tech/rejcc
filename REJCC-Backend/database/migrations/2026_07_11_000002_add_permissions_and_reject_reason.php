<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // null = accès complet ; sinon liste des sections admin autorisées.
            $table->json('permissions')->nullable()->after('role');
        });

        Schema::table('membership_applications', function (Blueprint $table) {
            $table->string('reject_reason', 500)->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });

        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropColumn('reject_reason');
        });
    }
};
