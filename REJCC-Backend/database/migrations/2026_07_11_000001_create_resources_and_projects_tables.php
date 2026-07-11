<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('type', 40)->default('Document'); // Ebook, Modèle, Vidéo, Audio, Document
            $table->text('description')->nullable();
            $table->string('url', 500);
            $table->string('size', 20)->nullable(); // ex : « 2.4 Mo »
            $table->unsignedInteger('downloads')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // porteur
            $table->string('title', 160);
            $table->text('description');
            $table->unsignedSmallInteger('members_count')->default(1);
            $table->string('status', 60)->default('En évaluation');
            $table->boolean('in_incubator')->default(false);
            $table->unsignedBigInteger('funding_goal')->nullable();  // FCFA
            $table->unsignedBigInteger('funding_raised')->default(0); // FCFA
            $table->json('milestones')->nullable(); // [{label, done}]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('resources');
    }
};
