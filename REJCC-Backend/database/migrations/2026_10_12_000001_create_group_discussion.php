<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discussion de groupe sur la plateforme : remplace le lien vers un groupe
 * WhatsApp externe. Chaque membre du groupe garde la date de sa dernière
 * lecture (messages non lus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['group_id', 'id']);
        });

        Schema::table('group_user', function (Blueprint $table) {
            $table->unsignedBigInteger('discussion_lu_jusqua')->default(0)->after('telephone_visible');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('whatsapp_url');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('whatsapp_url')->nullable()->after('couleur');
        });
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropColumn('discussion_lu_jusqua');
        });
        Schema::dropIfExists('group_messages');
    }
};
