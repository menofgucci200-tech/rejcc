<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->string('prenom')->nullable()->after('nom_prenoms');
            $table->string('nom')->nullable()->after('prenom');
        });

        foreach (DB::table('membership_applications')->select('id', 'nom_prenoms')->get() as $row) {
            $parts = preg_split('/\s+/', trim($row->nom_prenoms), 2);
            DB::table('membership_applications')->where('id', $row->id)->update([
                'prenom' => $parts[0] ?? $row->nom_prenoms,
                'nom' => $parts[1] ?? '',
            ]);
        }

        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropColumn('nom_prenoms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->string('nom_prenoms')->nullable()->after('id');
        });

        foreach (DB::table('membership_applications')->select('id', 'prenom', 'nom')->get() as $row) {
            DB::table('membership_applications')->where('id', $row->id)->update([
                'nom_prenoms' => trim(($row->prenom ?? '').' '.($row->nom ?? '')),
            ]);
        }

        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropColumn(['prenom', 'nom']);
        });
    }
};
