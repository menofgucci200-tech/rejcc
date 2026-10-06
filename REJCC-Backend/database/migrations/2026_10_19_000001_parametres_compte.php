<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paramètres du compte : notifications réelles (e-mail, push), appareils
 * connectés, changement d'e-mail, clôture du compte, journal du compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_notifications', function (Blueprint $table) {
            $table->timestamp('email_at')->nullable()->after('read_at');
            $table->timestamp('push_at')->nullable()->after('email_at');
            $table->index(['read_at', 'created_at']);
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->string('ip', 45)->nullable()->after('name');
            $table->string('agent', 255)->nullable()->after('ip');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email_nouveau')->nullable();
            $table->string('email_jeton', 64)->nullable();
            $table->timestamp('email_jeton_at')->nullable();
            $table->timestamp('suppression_prevue_at')->nullable();
            $table->string('suppression_motif', 500)->nullable();
            $table->timestamp('anonymise_at')->nullable();
        });

        Schema::create('account_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('detail', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint', 500);
            $table->string('endpoint_hash', 64)->unique();
            $table->string('p256dh', 200);
            $table->string('auth', 100);
            $table->string('agent', 255)->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamps();
        });

        // Les notifications déjà là ne déclenchent aucun e-mail ni push rétroactif.
        DB::table('member_notifications')->update(['email_at' => now(), 'push_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('account_events');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['email_nouveau', 'email_jeton', 'email_jeton_at', 'suppression_prevue_at', 'suppression_motif', 'anonymise_at']));
        Schema::table('api_tokens', fn (Blueprint $t) => $t->dropColumn(['ip', 'agent']));
        Schema::table('member_notifications', function (Blueprint $t) {
            $t->dropIndex(['read_at', 'created_at']);
            $t->dropColumn(['email_at', 'push_at']);
        });
    }
};
