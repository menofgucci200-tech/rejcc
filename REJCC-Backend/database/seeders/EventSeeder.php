<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'slug' => 'forum-entrepreneuriat-2026',
                'title' => "Forum REJCC de l'Entrepreneuriat",
                'category' => 'Forum',
                'location' => 'Abidjan, Plateau',
                'starts_at' => '2026-07-18 09:00:00',
                'time_label' => '09:00 – 17:00',
                'excerpt' => 'Une journée de conférences, de pitchs et de networking pour accélérer vos projets.',
                'description' => 'Une journée de conférences, de pitchs et de networking pour accélérer vos projets.',
                'body' => [
                    "Le rendez-vous annuel du réseau : une journée complète dédiée à l'entrepreneuriat des jeunes catholiques de Côte d'Ivoire.",
                    'Au programme : conférences inspirantes, sessions de pitchs, ateliers pratiques et moments de networking pour nouer des collaborations durables.',
                ],
                'capacity' => 300,
            ],
            [
                'slug' => 'atelier-lever-des-fonds',
                'title' => "Atelier — Lever des fonds en Côte d'Ivoire",
                'category' => 'Atelier',
                'location' => 'Abidjan, Cocody',
                'starts_at' => '2026-08-02 14:00:00',
                'time_label' => '14:00 – 17:00',
                'excerpt' => 'Maîtrisez les mécanismes de financement adaptés aux jeunes entreprises.',
                'description' => 'Maîtrisez les mécanismes de financement adaptés aux jeunes entreprises.',
                'body' => [
                    'Un atelier pratique pour comprendre les leviers de financement accessibles aux jeunes entreprises : fonds propres, prêts, subventions et investisseurs.',
                    'Repartez avec une feuille de route claire pour préparer votre dossier et convaincre.',
                ],
                'capacity' => 60,
            ],
            [
                'slug' => 'visite-entreprise-yamoussoukro',
                'title' => "Visite d'entreprise & mentorat",
                'category' => 'Visite',
                'location' => 'Yamoussoukro',
                'starts_at' => '2026-09-20 10:00:00',
                'time_label' => '10:00 – 13:00',
                'excerpt' => "Immersion dans une entreprise modèle, suivie d'une session de mentorat.",
                'description' => "Immersion dans une entreprise modèle, suivie d'une session de mentorat.",
                'body' => [
                    "Découvrez de l'intérieur une entreprise qui réussit : organisation, stratégie et bonnes pratiques.",
                    'La visite est suivie d\'une session de mentorat pour appliquer ces enseignements à vos propres projets.',
                ],
                'capacity' => 40,
            ],
            [
                'slug' => 'conference-foi-et-entrepreneuriat',
                'title' => 'Conférence — Foi & entrepreneuriat',
                'category' => 'Conférence',
                'location' => 'Abidjan',
                'starts_at' => '2026-10-05 18:00:00',
                'time_label' => '18:00 – 20:00',
                'excerpt' => 'Comment concilier valeurs chrétiennes et réussite entrepreneuriale.',
                'description' => 'Comment concilier valeurs chrétiennes et réussite entrepreneuriale.',
                'body' => [
                    "Une soirée de réflexion et d'inspiration sur le lien entre foi, éthique et entrepreneuriat.",
                    'Des intervenants partagent leur parcours et leur vision d\'une réussite porteuse de sens.',
                ],
                'capacity' => 150,
            ],
            [
                'slug' => 'gala-excellence-2026',
                'title' => "Gala de l'Excellence REJCC",
                'category' => 'Gala',
                'location' => 'Abidjan',
                'starts_at' => '2026-10-12 19:00:00',
                'time_label' => '19:00 – 23:00',
                'excerpt' => 'La soirée qui célèbre les talents, les projets et les réussites du réseau.',
                'description' => 'La soirée qui célèbre les talents, les projets et les réussites du réseau.',
                'body' => [
                    'Le grand rendez-vous festif du réseau : une soirée de prestige pour célébrer les réussites de l\'année.',
                    'Remises de distinctions, témoignages et moments de communion autour des valeurs du REJCC.',
                ],
                'capacity' => 250,
            ],
            [
                'slug' => 'soiree-networking-novembre',
                'title' => 'Soirée networking des membres',
                'category' => 'Networking',
                'location' => 'Abidjan, Marcory',
                'starts_at' => '2026-11-15 18:30:00',
                'time_label' => '18:30 – 21:00',
                'excerpt' => 'Rencontrez les membres du réseau dans un cadre convivial et professionnel.',
                'description' => 'Rencontrez les membres du réseau dans un cadre convivial et professionnel.',
                'body' => [
                    'Une soirée dédiée aux rencontres entre membres : échangez, partagez vos projets et créez des synergies.',
                    'Un format convivial pour développer votre réseau dans un esprit de confiance.',
                ],
                'capacity' => 120,
            ],
        ];

        foreach ($events as $e) {
            Event::updateOrCreate(['slug' => $e['slug']], $e);
        }
    }
}
