<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->string('ville')->nullable()->after('email');
            $table->string('password')->nullable()->after('ville');
            $table->string('statut')->default('en_attente')->after('traite');
            $table->foreignId('user_id')->nullable()->after('statut')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['ville', 'password', 'statut']);
        });
    }
};
