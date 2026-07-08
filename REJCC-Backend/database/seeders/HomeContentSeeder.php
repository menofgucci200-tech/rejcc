<?php

namespace Database\Seeders;

use App\Models\HomeBenefit;
use App\Models\HomeStat;
use App\Models\HomeValue;
use App\Models\MembershipStep;
use Illuminate\Database\Seeder;

class HomeContentSeeder extends Seeder
{
    public function run(): void
    {
        $stats = [
            ['label' => 'Membres engagés', 'value' => 350, 'suffix' => '+'],
            ['label' => "Domaines d'activité", 'value' => 33, 'suffix' => ''],
            ['label' => 'Événements par an', 'value' => 40, 'suffix' => '+'],
            ['label' => 'Mentors & experts', 'value' => 60, 'suffix' => '+'],
        ];
        foreach ($stats as $i => $s) {
            HomeStat::updateOrCreate(['label' => $s['label']], $s + ['ordre' => $i]);
        }

        $values = [
            ['icon' => 'flame', 'title' => 'Foi', 'text' => "S'inspirer des valeurs chrétiennes pour bâtir des relations durables, éthiques et porteuses de sens."],
            ['icon' => 'award', 'title' => 'Excellence', 'text' => "Rechercher la qualité, le professionnalisme et l'amélioration continue dans chaque projet."],
            ['icon' => 'heart-handshake', 'title' => 'Solidarité', 'text' => 'Avancer ensemble, partager les ressources et faire grandir chaque membre du réseau.'],
            ['icon' => 'target', 'title' => 'Impact', 'text' => "Créer de la valeur réelle au service de l'Église, de l'économie et de la société ivoirienne."],
            ['icon' => 'gem', 'title' => 'Création de richesse', 'text' => 'Transformer les talents en entreprises viables, innovantes et économiquement autonomes.'],
        ];
        foreach ($values as $i => $v) {
            HomeValue::updateOrCreate(['title' => $v['title']], $v + ['ordre' => $i]);
        }

        $benefits = [
            ['icon' => 'network', 'title' => 'Un réseau de qualité', 'text' => "Rejoignez une communauté triée d'entrepreneurs, de porteurs de projets et de décideurs catholiques."],
            ['icon' => 'graduation-cap', 'title' => 'Formations & mentorat', 'text' => 'Montez en compétences grâce à des programmes, ateliers et mentors expérimentés.'],
            ['icon' => 'rocket', 'title' => 'Accélérez vos projets', 'text' => "Bénéficiez d'un accompagnement, d'opportunités d'affaires et d'une mise en relation ciblée."],
            ['icon' => 'megaphone', 'title' => 'Gagnez en visibilité', 'text' => "Présentez votre entreprise à l'écosystème et aux partenaires du réseau."],
            ['icon' => 'hand-heart', 'title' => 'Une communauté de foi', 'text' => "Entreprendre dans un cadre de confiance, de valeurs partagées et d'entraide sincère."],
            ['icon' => 'gem', 'title' => 'Des opportunités concrètes', 'text' => "Accédez à des appels à projets, des financements et des visites d'entreprises."],
        ];
        foreach ($benefits as $i => $b) {
            HomeBenefit::updateOrCreate(['title' => $b['title']], $b + ['ordre' => $i]);
        }

        $steps = [
            ['icon' => 'user-plus', 'title' => 'Créez votre profil', 'text' => 'Renseignez votre activité, votre secteur et vos ambitions en quelques minutes.'],
            ['icon' => 'list-checks', 'title' => 'Précisez votre profil', 'text' => 'Étudiant, porteur de projet ou entrepreneur confirmé : dites-nous qui vous êtes.'],
            ['icon' => 'smartphone', 'title' => 'Soumettez votre formulaire', 'text' => "Remplissez notre formulaire d'inscription en ligne, ça ne prend que quelques minutes."],
            ['icon' => 'party-popper', 'title' => 'Rejoignez la communauté', 'text' => "Accédez à votre espace membre, à l'annuaire, aux événements et aux ressources."],
        ];
        foreach ($steps as $i => $s) {
            MembershipStep::updateOrCreate(['title' => $s['title']], $s + ['ordre' => $i]);
        }
    }
}
