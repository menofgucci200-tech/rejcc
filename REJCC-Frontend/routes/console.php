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

// Mesure d'audience : le ménage se fait de lui-même à la première visite de
// chaque jour (le site n'a pas de tâche planifiée) ; commande manuelle au besoin.
Artisan::command('audience:purger', function () {
    $b = \App\Support\Audience::maintenance();
    $this->info("{$b['empreintes']} journée(s) débarrassée(s) de leurs empreintes, {$b['supprimes']} journée(s) de plus de 13 mois supprimée(s).");
})->purpose("Mesure d'audience : efface les empreintes des jours passés et les agrégats trop anciens");
