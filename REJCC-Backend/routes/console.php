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
    $this->info("{$r['rappels']} rappel(s), {$r['expirees']} annonce(s) expirée(s), {$r['suspendues']} annonce(s) suspendue(s).");
})->purpose("Rappels et expiration des annonces de la Marketplace");

Schedule::command('marketplace:echeances')->dailyAt('07:00');

// Événements : rappel aux inscrits la veille (vérifié toutes les heures).
Artisan::command('evenements:rappels', function () {
    $n = 0;
    $evenements = \App\Models\Event::where('statut', 'publie')->whereBetween('starts_at', [now(), now()->addDay()])->get();
    foreach ($evenements as $e) {
        $quand = $e->starts_at->isToday() ? "aujourd'hui" : 'demain';
        foreach ($e->registrations()->whereNull('rappel_at')->get() as $r) {
            \App\Models\MemberNotification::create([
                'user_id' => $r->user_id,
                'type' => 'info',
                'title' => "Rappel : {$e->title} {$quand}",
                'body' => 'Rendez-vous '.$quand.' à '.$e->starts_at->format('H\hi').($e->en_ligne ? ' en ligne : le lien de connexion est sur la fiche.' : ($e->location ? " — {$e->location}." : '.')).' Votre billet est dans la fiche de l\'événement.',
                'link' => "/espace-membre/evenements?evenement={$e->id}",
            ]);
            $r->update(['rappel_at' => now()]);
            $n++;
        }
    }
    $this->info("{$n} rappel(s) envoyé(s).");
})->purpose('Rappel aux inscrits la veille des événements');

Schedule::command('evenements:rappels')->hourly();
