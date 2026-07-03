<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $opps = [
            [
                'title' => "Appel à projets — Incubateur REJCC",
                'type' => 'Appel à projets',
                'description' => "Le REJCC sélectionne 10 projets à incuber pour la saison 2026. Accompagnement, mentorat et accès au financement.",
                'contact' => 'incubateur@rejcc.ci',
                'deadline' => '2026-07-31',
            ],
            [
                'title' => "Fonds d'amorçage — Jeunes entrepreneurs",
                'type' => 'Financement',
                'description' => "Subvention de démarrage jusqu'à 2 000 000 FCFA pour les porteurs de projets membres en règle.",
                'contact' => 'fonds@rejcc.ci',
                'deadline' => '2026-08-15',
            ],
            [
                'title' => 'Recherche cofondateur technique (fintech)',
                'type' => 'Partenariat',
                'description' => "Startup de paiement mobile cherche un cofondateur développeur pour rejoindre l'aventure.",
                'contact' => 'koffi@example.ci',
                'deadline' => null,
            ],
            [
                'title' => 'Offre de stage — PME agroalimentaire',
                'type' => 'Offre',
                'description' => "Stage de 3 mois en marketing digital au sein d'une PME membre du réseau à Bouaké.",
                'contact' => 'rh@agropme.ci',
                'deadline' => '2026-07-10',
            ],
            [
                'title' => 'Mentor recherché — secteur santé',
                'type' => 'Partenariat',
                'description' => "Jeune porteuse de projet en e-santé cherche un mentor expérimenté du secteur.",
                'contact' => 'awa@example.ci',
                'deadline' => null,
            ],
        ];

        foreach ($opps as $o) {
            Opportunity::firstOrCreate(['title' => $o['title']], $o);
        }
    }
}
