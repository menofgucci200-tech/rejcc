<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['icon' => 'graduation-cap', 'title' => 'Formations', 'text' => 'Des parcours pour développer vos compétences entrepreneuriales, techniques et managériales.'],
            ['icon' => 'users', 'title' => 'Mentorat', 'text' => 'Un accompagnement personnalisé par des entrepreneurs et experts confirmés.'],
            ['icon' => 'mic', 'title' => 'Conférences', 'text' => 'Des rencontres inspirantes avec des leaders qui partagent leur vision et leur expérience.'],
            ['icon' => 'network', 'title' => 'Networking', 'text' => "Des moments privilégiés pour tisser des liens d'affaires et des collaborations durables."],
            ['icon' => 'building-2', 'title' => "Visites d'entreprises", 'text' => "Découvrir des modèles qui réussissent et s'en inspirer concrètement."],
            ['icon' => 'hand-heart', 'title' => 'Projets communautaires', 'text' => "Mettre l'entrepreneuriat au service de l'Église et de la société."],
            ['icon' => 'calendar-days', 'title' => 'Événements', 'text' => 'Forums, galas et célébrations qui rythment la vie du réseau.'],
            ['icon' => 'wrench', 'title' => 'Ateliers', 'text' => "Des sessions pratiques pour passer de l'idée à l'exécution."],
        ];

        foreach ($activities as $i => $a) {
            Activity::updateOrCreate(['title' => $a['title']], $a + ['ordre' => $i]);
        }
    }
}
