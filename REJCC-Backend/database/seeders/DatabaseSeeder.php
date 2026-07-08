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
        $this->call([
            SectorSeeder::class,
            ActivitySeeder::class,
            TestimonialSeeder::class,
            PartnerSeeder::class,
            HomeContentSeeder::class,
            NewsArticleSeeder::class,
            EventSeeder::class,
            OpportunitySeeder::class,
        ]);
    }
}
