<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sécurité : sort les anciennes pièces d'identité du dossier public du site
// (à lancer une fois après la mise en ligne ; sans effet ensuite).
Artisan::command('pieces:securiser', function () {
    $this->info(\App\Support\PiecesAnciennes::securiser().' pièce(s) déplacée(s) hors du dossier public.');
})->purpose("Retire les anciennes pièces d'identité du dossier public");
