<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
            $table->string('excerpt')->nullable()->after('description');
            $table->json('body')->nullable()->after('excerpt');
            $table->string('time_label')->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['slug', 'excerpt', 'body', 'time_label']);
        });
    }
};
