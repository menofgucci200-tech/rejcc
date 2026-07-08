<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            ['name' => "Diocèse d'Abidjan", 'initials' => 'DA', 'sector' => 'Institution'],
            ['name' => 'Banque Atlantique', 'initials' => 'BA', 'sector' => 'Finance'],
            ['name' => 'Orange CI', 'initials' => 'OC', 'sector' => 'Télécom'],
            ['name' => 'CGECI', 'initials' => 'CG', 'sector' => 'Patronat'],
            ['name' => 'Université Catholique', 'initials' => 'UC', 'sector' => 'Éducation'],
            ['name' => 'PME Excellence', 'initials' => 'PE', 'sector' => 'Conseil'],
            ['name' => 'Fondation Espoir', 'initials' => 'FE', 'sector' => 'ONG'],
            ['name' => 'AgriSol', 'initials' => 'AS', 'sector' => 'Agro'],
        ];

        foreach ($partners as $i => $p) {
            Partner::updateOrCreate(['name' => $p['name']], $p + ['ordre' => $i]);
        }
    }
}
