<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;

/**
 * Bibliothèque de départ : les fiches sont créées sans fichier (« bientôt
 * disponible ») ; l'équipe y joint ensuite le document depuis l'administration.
 */
class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [];
        foreach (['Officiel' => 'shield-check', 'Ressources' => 'book-open', 'Formations' => 'graduation-cap'] as $i => $icone) {
            $categories[$i] = DocumentCategory::firstOrCreate(['nom' => $i], ['icone' => $icone, 'ordre' => count($categories)])->id;
        }

        $docs = [
            ['title' => 'Statuts du REJCC', 'category' => 'Officiel', 'description' => 'Les statuts officiels du Réseau Entrepreneurial des Jeunes Chrétiens Catholiques.'],
            ['title' => "Charte de l'adhérent", 'category' => 'Officiel', 'description' => 'Les engagements et valeurs des membres.'],
            ['title' => 'Guide du membre', 'category' => 'Ressources', 'description' => "Bien démarrer dans l'espace membre."],
            ['title' => 'Modèle de business plan', 'category' => 'Ressources', 'description' => 'Un canevas pour structurer votre projet.'],
            ['title' => 'Programme des formations 2026', 'category' => 'Formations', 'description' => "Le calendrier des formations de l'année."],
        ];

        foreach ($docs as $d) {
            Document::firstOrCreate(['title' => $d['title']], $d + [
                'category_id' => $categories[$d['category']], 'acces' => 'tous', 'statut' => 'publie', 'publie_at' => now(),
            ]);
        }
    }
}
