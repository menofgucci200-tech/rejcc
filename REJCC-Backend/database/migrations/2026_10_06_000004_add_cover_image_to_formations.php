<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Image de couverture facultative (catalogue et fiche formation). */
    public function up(): void
    {
        Schema::table('formations', fn (Blueprint $table) => $table->string('image_url', 500)->nullable()->after('media_name'));
    }

    public function down(): void
    {
        Schema::table('formations', fn (Blueprint $table) => $table->dropColumn('image_url'));
    }
};
