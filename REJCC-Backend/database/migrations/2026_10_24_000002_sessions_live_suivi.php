<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi des sessions live : hôte Meet (la personne connectée au compte Google
 * du REJCC qui admet les participants), replay et support après la session.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('hote_meet', 120)->nullable()->after('animateur');
            $table->string('replay_url', 500)->nullable()->after('lien_visio');
            $table->string('support_url', 500)->nullable()->after('replay_url');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['hote_meet', 'replay_url', 'support_url']);
        });
    }
};
