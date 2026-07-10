<?php

namespace Database\Seeders;

use App\Models\Formation;
use Illuminate\Database\Seeder;

class FormationSeeder extends Seeder
{
    public function run(): void
    {
        $formations = [
            ['title' => "Fondamentaux de l'entrepreneuriat", 'category' => 'Entrepreneuriat', 'duration' => '5 semaines', 'level' => 'Débutant', 'is_free' => true, 'is_certifying' => true, 'modules_count' => 5],
            ['title' => 'Rédiger un business plan', 'category' => 'Entrepreneuriat', 'duration' => '5 semaines', 'level' => 'Intermédiaire', 'is_free' => false, 'is_certifying' => true, 'modules_count' => 6],
            ['title' => 'Lever des fonds pour son projet', 'category' => 'Entrepreneuriat', 'duration' => '6 semaines', 'level' => 'Avancé', 'is_free' => false, 'is_certifying' => true, 'modules_count' => 6],
            ['title' => 'Leadership chrétien — Diriger avec intégrité', 'category' => 'Leadership', 'duration' => '6 semaines', 'level' => 'Intermédiaire', 'is_free' => true, 'is_certifying' => true, 'modules_count' => 8],
            ['title' => 'Initiation à la comptabilité', 'category' => 'Finance', 'duration' => '4 semaines', 'level' => 'Débutant', 'is_free' => true, 'is_certifying' => false, 'modules_count' => 5],
            ['title' => 'Gestion financière pour entrepreneurs', 'category' => 'Finance', 'duration' => '5 semaines', 'level' => 'Intermédiaire', 'is_free' => false, 'is_certifying' => true, 'modules_count' => 5],
            ['title' => 'Prise de parole en public', 'category' => 'Communication', 'duration' => '3 semaines', 'level' => 'Débutant', 'is_free' => true, 'is_certifying' => true, 'modules_count' => 6],
            ['title' => 'Marketing digital pour PME', 'category' => 'Marketing', 'duration' => '4 semaines', 'level' => 'Intermédiaire', 'is_free' => false, 'is_certifying' => true, 'modules_count' => 6],
            ['title' => 'Éthique chrétienne au travail', 'category' => 'Spiritualité', 'duration' => '2 semaines', 'level' => 'Tous niveaux', 'is_free' => true, 'is_certifying' => false, 'modules_count' => 4],
            ['title' => 'Gestion du temps et priorités', 'category' => 'Productivité', 'duration' => '2 semaines', 'level' => 'Débutant', 'is_free' => true, 'is_certifying' => false, 'modules_count' => 4],
        ];

        foreach ($formations as $f) {
            Formation::firstOrCreate(['title' => $f['title']], $f);
        }
    }
}
