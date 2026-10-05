<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Marketplace : rappels avant expiration et annonces expirées (chaque matin).
Artisan::command('marketplace:echeances', function () {
    $r = \App\Models\MarketplaceListing::traiterEcheances();
    $this->info("{$r['rappels']} rappel(s), {$r['expirees']} annonce(s) expirée(s).");
})->purpose("Rappels et expiration des annonces de la Marketplace");

Schedule::command('marketplace:echeances')->dailyAt('07:00');
