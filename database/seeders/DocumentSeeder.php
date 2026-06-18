<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $docs = [
            ['title' => 'Statuts du REJCC', 'category' => 'Officiel', 'description' => 'Les statuts officiels du réseau.', 'url' => '#', 'size' => 'PDF'],
            ['title' => "Charte de l'adhérent", 'category' => 'Officiel', 'description' => 'Les engagements et valeurs des membres.', 'url' => '#', 'size' => 'PDF'],
            ['title' => 'Guide du membre', 'category' => 'Ressources', 'description' => "Bien démarrer dans l'espace membre.", 'url' => '#', 'size' => 'PDF'],
            ['title' => "Modèle de business plan", 'category' => 'Ressources', 'description' => 'Un canevas pour structurer votre projet.', 'url' => '#', 'size' => 'DOCX'],
            ['title' => 'Programme des formations 2026', 'category' => 'Formations', 'description' => "Le calendrier des formations de l'année.", 'url' => '#', 'size' => 'PDF'],
        ];

        foreach ($docs as $d) {
            Document::firstOrCreate(['title' => $d['title']], $d);
        }
    }
}
