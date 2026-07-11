<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Uniquement le contenu institutionnel de la vitrine : le contenu
        // éditorial (actualités, événements, formations, témoignages…) est
        // publié par les administrateurs depuis le dashboard.
        $this->call([
            SectorSeeder::class,
            ActivitySeeder::class,
            HomeContentSeeder::class,
        ]);
    }
}
