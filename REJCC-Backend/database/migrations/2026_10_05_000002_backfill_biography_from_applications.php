<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Membres déjà acceptés : reprend paroisse, diocèse, activité et compétences
     * déclarées dans la demande d'adhésion, sans écraser un champ déjà rempli.
     */
    public function up(): void
    {
        $applications = DB::table('membership_applications')->whereNotNull('user_id')->get();

        foreach ($applications as $a) {
            $user = DB::table('users')->where('id', $a->user_id)->first();
            if (! $user) {
                continue;
            }

            $updates = array_filter([
                'paroisse' => $user->paroisse ? null : $a->paroisse,
                'diocese' => $user->diocese ? null : $a->diocese,
                'organisation' => $user->organisation ? null : $a->nom_activite,
                'competences' => $user->competences ? null : $a->competences,
            ], fn ($v) => $v !== null && $v !== '' && $v !== '[]');

            if ($updates) {
                DB::table('users')->where('id', $a->user_id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Données reprises : rien à annuler.
    }
};
