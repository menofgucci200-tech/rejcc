<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['in_incubator', 'funding_goal', 'funding_raised', 'milestones']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('in_incubator')->default(false);
            $table->unsignedBigInteger('funding_goal')->nullable();
            $table->unsignedBigInteger('funding_raised')->default(0);
            $table->json('milestones')->nullable();
        });
    }
};
