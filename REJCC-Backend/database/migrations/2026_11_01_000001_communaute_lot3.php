<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Communauté, lot 3 : sujets séparés dans les grands salons (comme les
 * « topics » de Telegram), annonces programmées et discussions vocales en
 * direct (réunion Google Meet du compte REJCC ou lien de visio).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->boolean('sujets_actives')->default(false)->after('regles');
        });

        Schema::create('salon_sujets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->string('titre', 80);
            $table->string('emoji', 16)->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ferme_at')->nullable();
            $table->timestamp('dernier_message_at')->nullable();
            $table->timestamps();
            $table->index(['salon_id', 'dernier_message_at']);
        });

        Schema::table('salon_messages', function (Blueprint $table) {
            $table->foreignId('sujet_id')->nullable()->after('salon_id')->constrained('salon_sujets')->nullOnDelete();
            $table->index(['salon_id', 'sujet_id', 'id']);
        });

        Schema::create('salon_programmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('publier_a');
            $table->foreignId('salon_message_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('publie_at')->nullable();
            $table->timestamp('annule_at')->nullable();
            $table->timestamps();
            $table->index(['publie_at', 'annule_at', 'publier_a']);
        });

        Schema::create('salon_vocaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salon_message_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lance_par')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titre', 120);
            $table->string('lien', 500);
            $table->string('fournisseur', 20)->default('lien'); // meet | lien
            $table->string('google_event_id', 200)->nullable();
            $table->timestamp('debut_at');
            $table->timestamp('fin_at')->nullable();
            $table->foreignId('termine_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_vocaux');
        Schema::dropIfExists('salon_programmations');
        Schema::table('salon_messages', function (Blueprint $table) {
            $table->dropIndex(['salon_id', 'sujet_id', 'id']);
            $table->dropConstrainedForeignId('sujet_id');
        });
        Schema::dropIfExists('salon_sujets');
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn('sujets_actives');
        });
    }
};
