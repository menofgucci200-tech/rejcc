<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Inscriptions aux événements : billet (code du QR), pointage le jour J, rappel envoyé la veille. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('billet', 16)->nullable()->unique()->after('user_id');
            $table->timestamp('present_at')->nullable()->after('billet');
            $table->timestamp('rappel_at')->nullable()->after('present_at');
        });

        foreach (DB::table('event_registrations')->whereNull('billet')->pluck('id') as $id) {
            DB::table('event_registrations')->where('id', $id)->update(['billet' => 'B-'.strtoupper(Str::random(8))]);
        }
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropUnique(['billet']);
            $table->dropColumn(['billet', 'present_at', 'rappel_at']);
        });
    }
};
