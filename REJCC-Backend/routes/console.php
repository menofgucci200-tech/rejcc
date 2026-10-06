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

// Emploi & Stage : rappel à l'auteur 3 jours avant l'expiration de son offre.
Artisan::command('emplois:echeances', function () {
    $this->info(\App\Models\Opportunity::rappelsExpiration().' rappel(s) envoyé(s).');
})->purpose("Rappels d'expiration des offres d'emploi et de stage");

Schedule::command('emplois:echeances')->dailyAt('07:10');

// Documents personnels : rappel au membre 30 jours avant l'expiration d'une pièce.
Artisan::command('documents:expirations', function () {
    $this->info(\App\Models\PersonalDocument::rappelsExpiration().' rappel(s) envoyé(s).');
})->purpose("Rappels d'expiration des documents personnels des membres");

Schedule::command('documents:expirations')->dailyAt('07:20');

// Événements : rappel aux inscrits la veille (vérifié toutes les heures).
Artisan::command('evenements:rappels', function () {
    $n = 0;
    $evenements = \App\Models\Event::where('statut', 'publie')->whereBetween('starts_at', [now(), now()->addDay()])->get();
    foreach ($evenements as $e) {
        $quand = $e->starts_at->isToday() ? "aujourd'hui" : 'demain';
        foreach ($e->registrations()->whereNull('rappel_at')->get() as $r) {
            $titre = "Rappel : {$e->title} {$quand}";
            $heure = 'Rendez-vous '.$quand.' à '.$e->starts_at->format('H\hi');
            if ($r->user_id) {
                \App\Models\MemberNotification::create([
                    'user_id' => $r->user_id,
                    'type' => 'info',
                    'title' => $titre,
                    'body' => $heure.($e->en_ligne ? ' en ligne : le lien de connexion est sur la fiche.' : ($e->location ? " — {$e->location}." : '.')).' Votre billet est dans la fiche de l\'événement.',
                    'link' => "/espace-membre/evenements?evenement={$e->id}",
                ]);
            } elseif ($r->email) {
                // Invité du formulaire public : rappel par e-mail (lien visio inclus pour un événement en ligne).
                \App\Support\Mailer::send($r->email, new \App\Mail\InfoEvenement($r, $e, $titre,
                    $heure.($e->en_ligne ? ($e->lien_visio ? " en ligne : {$e->lien_visio}" : ' en ligne.') : ($e->location ? " — {$e->location}." : '.')).' Présentez le QR code de votre billet à l\'entrée.'));
            }
            $r->update(['rappel_at' => now()]);
            $n++;
        }
    }
    $this->info("{$n} rappel(s) envoyé(s).");
})->purpose('Rappel aux inscrits la veille des événements');

Schedule::command('evenements:rappels')->hourly();

// Certificats : rattrapage des certificats et attestations non encore délivrés
// (attestations d'événement une fois l'événement terminé).
Artisan::command('certificats:delivrer', function () {
    $n = \App\Support\Certificats::rattraper();
    $this->info("{$n['formation']} certificat(s) de formation, {$n['evenement']} attestation(s) d'événement, {$n['parcours']} attestation(s) de parcours délivré(s).");
})->purpose('Délivre les certificats et attestations en attente');

Schedule::command('certificats:delivrer')->hourlyAt(25);

// Abonnements : rappels d'échéance (J-30, J-7, échéance, fin du délai de grâce)
// et revérification des paiements restés en attente auprès de CinetPay.
Artisan::command('abonnements:echeances', function () {
    $this->info(\App\Support\Abonnement::rappels().' rappel(s) envoyé(s).');
})->purpose("Rappels d'échéance des abonnements");

Schedule::command('abonnements:echeances')->dailyAt('08:05');

Artisan::command('abonnements:paiements', function () {
    $n = \App\Support\Abonnement::verifierEnAttente();
    $this->info("{$n['confirmes']} paiement(s) confirmé(s), {$n['abandonnes']} abandonné(s).");
})->purpose('Revérifie les paiements en attente auprès de CinetPay');

Schedule::command('abonnements:paiements')->everyThirtyMinutes();
