<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['formations', 'opportunities', 'news_articles'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('media_url', 500)->nullable();
                $t->string('media_name', 200)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['formations', 'opportunities', 'news_articles'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['media_url', 'media_name']);
            });
        }
    }
};
