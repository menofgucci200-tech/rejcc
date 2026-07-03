<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            ['icon' => 'sprout', 'title' => 'Agriculture & Agro', 'blurb' => "Nourrir l'avenir, de la terre à l'assiette.", 'items' => ['Agriculture', 'Agroalimentaire', 'Élevage', 'Pêche']],
            ['icon' => 'cpu', 'title' => 'Tech & Numérique', 'blurb' => 'Innover et bâtir les solutions de demain.', 'items' => ['Informatique', 'Développement Web', 'Cybersécurité', 'Intelligence Artificielle']],
            ['icon' => 'megaphone', 'title' => 'Communication & Création', 'blurb' => 'Faire rayonner les marques et les idées.', 'items' => ['Communication', 'Marketing', 'Audiovisuel', 'Design']],
            ['icon' => 'landmark', 'title' => 'Finance & Services', 'blurb' => 'Structurer, financer et sécuriser la croissance.', 'items' => ['Finance', 'Comptabilité', 'Banque', 'Assurance', 'Ressources humaines', 'Juridique']],
            ['icon' => 'heart-pulse', 'title' => 'Éducation & Santé', 'blurb' => "Servir l'humain, du savoir au bien-être.", 'items' => ['Éducation', 'Santé', 'Beauté']],
            ['icon' => 'building-2', 'title' => 'Immobilier & BTP', 'blurb' => 'Construire des cadres de vie durables.', 'items' => ['Immobilier', 'BTP']],
            ['icon' => 'shopping-bag', 'title' => 'Commerce & Mobilité', 'blurb' => 'Faire circuler la valeur et les personnes.', 'items' => ['Commerce', 'E-commerce', 'Transport', 'Hôtellerie', 'Tourisme']],
            ['icon' => 'scissors', 'title' => 'Artisanat & Mode', 'blurb' => "Le savoir-faire et l'élégance ivoirienne.", 'items' => ['Artisanat', 'Couture']],
            ['icon' => 'leaf', 'title' => 'Impact & Énergie', 'blurb' => 'Entreprendre pour la planète et la communauté.', 'items' => ['Énergies renouvelables', 'ONG', 'Développement communautaire']],
        ];

        foreach ($sectors as $i => $s) {
            Sector::updateOrCreate(['title' => $s['title']], $s + ['ordre' => $i]);
        }
    }
}
