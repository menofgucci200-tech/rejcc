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

// Événements : rappel aux inscrits la veille, puis une heure avant pour les
// événements en ligne (avec e-mail immédiat), vérifié toutes les 10 minutes.
Artisan::command('evenements:rappels', function () {
    $n = 0;
    $evenements = \App\Models\Event::where('statut', 'publie')->whereBetween('starts_at', [now(), now()->addDay()])->get();
    foreach ($evenements as $e) {
        $heure = $e->starts_at->format('H\hi');
        $bientot = $e->en_ligne && $e->starts_at->isBefore(now()->addMinutes(75));
        $lien = "/espace-membre/evenements?evenement={$e->id}";
        $inscriptions = $e->registrations()->with('user')->get()
            ->filter(fn ($r) => $bientot ? $r->rappel_h1_at === null : $r->rappel_at === null);
        foreach ($inscriptions as $r) {
            if ($bientot) {
                // Une heure avant : lien direct, e-mail tout de suite.
                $titre = "Dans 1 h : {$e->title}";
                $texte = "Votre session en visio commence à {$heure}. Ouvrez-la sur la plateforme et cliquez sur « Rejoindre la visio » (actif 15 min avant). Une personne du REJCC vous fera entrer.";
            } else {
                $quand = $e->starts_at->isToday() ? "aujourd'hui" : 'demain';
                $titre = "Rappel : {$e->title} {$quand}";
                $texte = "Rendez-vous {$quand} à {$heure}".($e->en_ligne
                    ? ' en visio. Le moment venu, ouvrez la fiche de la session et cliquez sur « Rejoindre la visio ».'
                    : ($e->location ? " — {$e->location}." : '.').' Votre billet est dans la fiche de l\'événement.');
            }
            if ($r->user_id) {
                \App\Models\MemberNotification::create(['user_id' => $r->user_id, 'type' => 'info', 'title' => $titre, 'body' => $texte, 'link' => $lien]);
                $u = $r->user;
                if ($bientot && $u && $u->email && ! \App\Support\Notifications::enPause($u)
                    && (\App\Support\Notifications::reglages($u)['email']['evenements'] ?? 'quotidien') !== 'jamais') {
                    \App\Support\Mailer::send($u->email, new \App\Mail\RappelSessionLive($u->prenom, $e, $titre, $texte,
                        rtrim((string) config('app.frontend_url'), '/').$lien));
                }
            } elseif ($r->email) {
                // Invité du formulaire public : rappel par e-mail (lien visio inclus pour un événement en ligne).
                \App\Support\Mailer::send($r->email, new \App\Mail\InfoEvenement($r, $e, $titre,
                    "Rendez-vous à {$heure}".($e->en_ligne ? ($e->lien_visio ? " en ligne : {$e->lien_visio}" : ' en ligne.') : ($e->location ? " — {$e->location}." : '.').' Présentez le QR code de votre billet à l\'entrée.')));
            }
            // Le rappel d'une heure avant tient lieu de rappel de la veille s'il arrive seul.
            $r->update($bientot ? ['rappel_h1_at' => now(), 'rappel_at' => $r->rappel_at ?? now()] : ['rappel_at' => now()]);
            $n++;
        }
    }
    $this->info("{$n} rappel(s) envoyé(s).");
})->purpose('Rappels aux inscrits : la veille, puis une heure avant les événements en ligne');

Schedule::command('evenements:rappels')->everyTenMinutes();

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

// Notifications par e-mail selon les réglages des membres : « tout de suite »
// (notification restée non lue 10 min) et résumé quotidien du matin.
Artisan::command('notifications:emails', function () {
    $this->info(\App\Support\Notifications::envoyerImmediats().' e-mail(s) envoyé(s).');
})->purpose('E-mails des notifications « tout de suite »');

Schedule::command('notifications:emails')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('notifications:resume', function () {
    $this->info(\App\Support\Notifications::envoyerResumes().' résumé(s) envoyé(s).');
})->purpose('Résumé quotidien des notifications non lues');

Schedule::command('notifications:resume')->dailyAt('07:30');

// Comptes clôturés : anonymisation définitive 30 jours après la demande.
Artisan::command('comptes:clotures', function () {
    $this->info(\App\Support\Cloture::anonymiserEcheances().' compte(s) supprimé(s) définitivement.');
})->purpose('Anonymise les comptes dont la clôture est arrivée à échéance');

Schedule::command('comptes:clotures')->dailyAt('03:10');

// Notifications sur les appareils (Web Push) : envoi à la minute.
Artisan::command('notifications:push', function () {
    $this->info(\App\Support\Notifications::envoyerPush().' notification(s) envoyée(s) sur les appareils.');
})->purpose('Notifications push sur les appareils des membres');

Schedule::command('notifications:push')->everyMinute()->withoutOverlapping();
