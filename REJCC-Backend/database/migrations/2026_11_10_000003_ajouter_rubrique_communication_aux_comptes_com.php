<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nouvelle rubrique « communication » (Espace Communication : CathoImpact…) :
 * les comptes existants du département Communication la reçoivent. Les
 * comptes à accès complet (permissions null) n'en ont pas besoin.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->modifier(function (array $permissions) {
            if (! in_array('communication', $permissions, true)) {
                $permissions[] = 'communication';
            }

            return $permissions;
        });
    }

    public function down(): void
    {
        $this->modifier(fn (array $permissions) => array_values(array_diff($permissions, ['communication'])));
    }

    private function modifier(callable $transformer): void
    {
        DB::table('users')->where('bureau_departement', 'communication')->whereNotNull('permissions')
            ->get(['id', 'permissions'])
            ->each(function ($u) use ($transformer) {
                $permissions = json_decode((string) $u->permissions, true);
                if (! is_array($permissions)) {
                    return;
                }
                DB::table('users')->where('id', $u->id)->update(['permissions' => json_encode($transformer($permissions))]);
            });
    }
};
