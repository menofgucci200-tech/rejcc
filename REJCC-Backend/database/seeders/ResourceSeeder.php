<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            ['title' => 'Guide pratique — Créer son business plan', 'type' => 'Ebook', 'url' => 'https://rejcc.site/documents', 'size' => '2.4 Mo'],
            ['title' => 'Modèle de prévisionnel financier', 'type' => 'Modèle', 'url' => 'https://rejcc.site/documents', 'size' => '180 Ko'],
            ['title' => 'Statuts et charte du réseau', 'type' => 'Document', 'url' => 'https://rejcc.site/documents', 'size' => '1.1 Mo'],
        ];

        foreach ($resources as $r) {
            Resource::firstOrCreate(['title' => $r['title']], $r);
        }
    }
}
