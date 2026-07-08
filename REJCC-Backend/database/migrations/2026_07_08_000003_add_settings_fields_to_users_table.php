<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_naissance')->nullable()->after('ville');
            $table->string('paroisse')->nullable()->after('date_naissance');
            $table->json('preferences')->nullable()->after('bio');
            $table->string('reference')->nullable()->unique()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['date_naissance', 'paroisse', 'preferences', 'reference']);
        });
    }
};
