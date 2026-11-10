<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\Content\DailyWord;
use App\Support\ProfileCompletion;
use App\Support\Quand;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Accueil de l'espace membre, en trois niveaux :
 *  1. « À faire maintenant » : au plus 3 actions personnalisées, chacune avec
 *     son bouton (participer à l'événement du jour, confirmer une séance de
 *     mentorat, lire ses messages, reprendre sa formation, compléter son
 *     profil, s'inscrire au prochain événement…) ;
 *  2. mes formations et mes événements, côte à côte ;
 *  3. le reste, allégé : parole du jour, membres du réseau, « Ma progression »
 *     (badges et défis) et activité récente.
 */
#[Layout('layouts.member-light')]
class Dashboard extends Component
{
    /**
     * Défis calculés depuis les vraies actions du membre — plus de cases à
     * cocher factices : chaque défi se valide tout seul quand l'action est faite.
     */
    protected function defis(Collection $formations, Collection $events, int $completion): array
    {
        return [
            [
                'label' => 'S\'inscrire à une formation du catalogue',
                'xp' => 50,
                'fait' => $formations->isNotEmpty(),
            ],
            [
                'label' => 'Faire avancer une formation (valider un module)',
                'xp' => 80,
                'fait' => $formations->contains(fn (array $f) => $f['completed'] || $f['progress'] > 0),
            ],
            [
                'label' => 'S\'inscrire à un événement du réseau',
                'xp' => 80,
                'fait' => $events->contains(fn (array $e) => ! empty($e['registered'])),
            ],
            [
                'label' => 'Compléter son profil à 100 %',
                'xp' => 120,
                'fait' => $completion >= 100,
            ],
        ];
    }

    /**
     * Inscription en un clic au prochain événement, depuis « À faire
     * maintenant ». La confirmation propose la suite (retrouver l'inscription).
     */
    public function inscrireEvenement(int $id): void
    {
        $r = Api::post("/events/{$id}/inscription", [], Api::token());

        $this->dispatch('rj-toast',
            message: ($r['ok'] ?? false)
                ? 'Inscription confirmée ! Vous la retrouvez dans Événements › Mes inscriptions, avec l\'ajout à votre agenda.'
                : ($r['message'] ?? 'L\'inscription n\'a pas pu aboutir. Réessayez depuis la page Événements.'),
            type: ($r['ok'] ?? false) ? 'succes' : 'erreur',
        );
    }

    public function render()
    {
        $token = Api::token();

        // L'utilisateur en session date de la connexion : on le relit pour que
        // l'état de l'abonnement (et donc le menu verrouillé) soit toujours juste.
        $me = Api::get('/auth/me', [], $token);
        if ($me['ok'] ?? false) {
            session(['api_user' => $me['user']]);
        }
        $user = Api::user();
        $completion = ProfileCompletion::percent($user);
        $champsManquants = ProfileCompletion::missing($user);

        $abonnementActif = (bool) ($user->subscription_active ?? false);
        $expireLe = ! empty($user->subscription_expires_at) ? Carbon::parse($user->subscription_expires_at) : null;
        // Rappel de renouvellement dans les 30 derniers jours.
        $expireBientot = $abonnementActif && $expireLe && $expireLe->isFuture() && $expireLe->lte(now()->addDays(30));

        // Annuaire et messagerie sont réservés aux abonnés (402 sinon) : inutile de les appeler.
        $conversations = Collection::make($abonnementActif ? (Api::get('/messages', [], $token)['conversations'] ?? []) : []);
        $unreadMessages = (int) $conversations->sum('unread');

        $members = Collection::make($abonnementActif ? (Api::get('/members', [], $token)['members'] ?? []) : [])
            ->reject(fn ($m) => $m['id'] === $user->id)
            ->take(4)->map(fn ($m) => (object) $m);

        $events = Collection::make(Api::get('/events', [], $token)['events'] ?? []);
        $aVenir = $events
            ->filter(fn ($e) => ($e['statut'] ?? 'publie') === 'publie' && ! ($e['passe'] ?? false))
            ->sortBy('starts_at')->values();

        $formations = Collection::make(Api::get('/my-formations', [], $token)['formations'] ?? []);

        // Statistiques d'apprentissage réelles.
        $terminees = $formations->where('completed', true)->count();
        $certificats = count(Api::get('/my-certificates', [], $token)['certificates'] ?? []);
        $progressionMoyenne = $formations->isNotEmpty()
            ? (int) round($formations->avg(fn ($f) => $f['completed'] ? 100 : (int) $f['progress']))
            : 0;

        // Formations en cours, la plus récemment suivie d'abord (comme « Mes formations »).
        $enCours = $formations
            ->reject(fn ($f) => $f['completed'])
            ->sortByDesc(fn ($f) => $f['derniere_activite'] ?? '')
            ->map(function (array $f) {
                $modules = max(1, (int) $f['modules_count']);
                $moduleCourant = min($modules, (int) ceil($f['progress'] / 100 * $modules) + 1);

                return [
                    'titre' => $f['title'],
                    'categorie' => $f['category'],
                    'pct' => (int) $f['progress'],
                    // Nom réel du prochain module quand la formation a un contenu détaillé.
                    'module' => ! empty($f['module_courant'])
                        ? "Prochain module : {$f['module_courant']} ({$moduleCourant} sur {$modules})"
                        : "Module {$moduleCourant} sur {$modules}",
                    // Ouvre directement la formation (ses modules) ; sinon la page « Mes formations ».
                    'url' => ! empty($f['has_modules'])
                        ? route('espace-membre.formations.detail', $f['id'])
                        : route('espace-membre.formations'),
                ];
            })
            ->values();
        $continuer = $enCours->first();

        // Formation à commencer : seulement si rien n'est en cours (évite un appel inutile).
        $aCommencer = null;
        if (! $continuer) {
            $f = Collection::make(Api::get('/formations', [], $token)['formations'] ?? [])->first(fn ($f) => ! $f['enrolled']);
            $aCommencer = $f ? [
                'titre' => $f['title'],
                'detail' => $f['category'].($f['is_certifying'] ? ' · certifiante' : ''),
                'url' => route('espace-membre.catalogue.fiche', $f['id']),
            ] : null;
        }

        $defis = $this->defis($formations, $events, $completion);

        $prochaineSeance = Api::get('/mentorat/prochaine-seance', [], $token)['seance'] ?? null;

        $badgesParcours = Collection::make(Api::get('/paths', [], $token)['paths'] ?? [])
            ->where('badge_obtenu', true)
            ->sortByDesc('badge_obtenu_le')
            ->values();

        $activites = Collection::make(Api::get('/my-activity', [], $token)['activity'] ?? [])
            ->take(4)
            ->map(fn (array $a) => [
                'texte' => $a['text'],
                'quand' => ucfirst(Carbon::parse($a['at'])->diffForHumans()),
                'dot' => $a['color'],
            ])
            ->all();

        return view('livewire.member.dashboard', [
            'aFaire' => $this->aFaire($aVenir, $prochaineSeance, $unreadMessages, $continuer, $aCommencer, $completion, $champsManquants),
            'completion' => $completion,
            'parole' => DailyWord::today(),
            'abonnementActif' => $abonnementActif,
            'enGrace' => (bool) ($user->subscription_grace ?? false),
            'graceFin' => ! empty($user->subscription_grace_fin) ? Carbon::parse($user->subscription_grace_fin) : null,
            'expireLe' => $expireLe,
            'expireBientot' => $expireBientot,
            'members' => $members,
            // Les 4 prochains événements, avec « Inscrit » ou l'inscription en un clic.
            'evenements' => $aVenir->take(4)->values(),
            'nbInscriptions' => $aVenir->where('registered', true)->count(),
            'enCours' => $enCours->take(3),
            'aCommencer' => $aCommencer,
            'defis' => $defis,
            'defisFaits' => collect($defis)->where('fait', true)->count(),
            'badgesParcours' => $badgesParcours,
            'activites' => $activites,
            'progression' => $progressionMoyenne,
            'formationsTerminees' => $terminees,
            'certificatsObtenus' => $certificats,
            'modulesValides' => (int) $formations->sum(fn ($f) => (int) ($f['modules_done'] ?? 0)),
        ]);
    }

    /**
     * Les actions de « À faire maintenant », de la plus urgente à la moins
     * urgente ; seules les 3 premières sont affichées. Chaque action : icône,
     * titre, détail, bouton (lien, ou inscription directe à un événement).
     */
    protected function aFaire(Collection $aVenir, ?array $seance, int $nonLus, ?array $continuer, ?array $aCommencer, int $completion, array $champsManquants): array
    {
        $actions = [];

        // 1. Un événement auquel je suis inscrit a lieu aujourd'hui (ou est en cours).
        $duJour = $aVenir->first(fn ($e) => ($e['registered'] ?? false)
            && (($e['en_cours'] ?? false) || Carbon::parse($e['starts_at'])->setTimezone(config('app.timezone'))->isToday()));
        if ($duJour) {
            $actions[] = [
                'cle' => 'participer', 'icon' => ($duJour['en_ligne'] ?? false) ? 'video' : 'calendar-days', 'urgent' => true,
                'titre' => ($duJour['en_cours'] ?? false) ? 'En ce moment : '.$duJour['title'] : 'Aujourd\'hui : '.$duJour['title'],
                'detail' => ($duJour['en_cours'] ?? false) ? 'La session a commencé.' : 'Commence '.Quand::delai($duJour['starts_at']).'.',
                'evenement' => $duJour,
                'bouton' => ($duJour['en_ligne'] ?? false) ? 'Voir la session' : 'Voir mon billet',
                'url' => route('espace-membre.evenements', ['evenement' => $duJour['id']]),
            ];
        }

        // 2. Une séance de mentorat attend ma confirmation.
        if ($seance && ($seance['statut'] ?? null) === 'proposee' && ! ($seance['proposee_par_moi'] ?? false)) {
            $actions[] = [
                'cle' => 'mentorat', 'icon' => 'hand-heart', 'urgent' => true,
                'titre' => 'Confirmer votre séance de mentorat',
                'detail' => ucfirst(Carbon::parse($seance['debut'])->translatedFormat('l j F \à H\hi')).' avec '.$seance['avec'].'.',
                'bouton' => 'Confirmer la séance',
                'url' => route('espace-membre.mentorat.suivi', $seance['mentorship_id']),
            ];
            $seance = null;
        }

        // 3. Messages non lus.
        if ($nonLus > 0) {
            $actions[] = [
                'cle' => 'messages', 'icon' => 'message-circle',
                'titre' => $nonLus > 1 ? "Vous avez {$nonLus} messages non lus" : 'Vous avez 1 message non lu',
                'detail' => 'Des membres vous ont écrit.',
                'bouton' => 'Lire mes messages',
                'url' => route('espace-membre.messaging'),
            ];
        }

        // 4. Formation en cours à reprendre.
        if ($continuer) {
            $actions[] = [
                'cle' => 'formation', 'icon' => 'graduation-cap',
                'titre' => 'Reprendre : '.$continuer['titre'],
                'detail' => $continuer['module'].' · '.$continuer['pct'].' % fait.',
                'pct' => $continuer['pct'],
                'bouton' => 'Reprendre ma formation',
                'url' => $continuer['url'],
            ];
        }

        // 5. Profil incomplet.
        if ($completion < 100) {
            $actions[] = [
                'cle' => 'profil', 'icon' => 'user',
                'titre' => "Compléter votre profil ({$completion} %)",
                'detail' => 'Il manque : '.implode(', ', array_slice($champsManquants, 0, 3)).(count($champsManquants) > 3 ? '…' : '.'),
                'pct' => $completion,
                'bouton' => 'Compléter mon profil',
                'url' => route('espace-membre.profile'),
            ];
        }

        // 6. Prochain événement ouvert aux inscriptions : inscription en un clic.
        $prochain = $aVenir->first(fn ($e) => ! ($e['registered'] ?? false) && empty($e['refus']) && ! ($e['en_cours'] ?? false));
        if ($prochain) {
            $debut = Carbon::parse($prochain['starts_at'])->setTimezone(config('app.timezone'));
            $actions[] = [
                'cle' => 'evenement', 'icon' => 'calendar-days',
                'titre' => $prochain['title'],
                'detail' => ucfirst($debut->translatedFormat('l j F \à H\hi')).(($prochain['en_ligne'] ?? false) ? ' · en ligne' : ($prochain['location'] ? ' · '.$prochain['location'] : '')).'.',
                'bouton' => 'M\'inscrire',
                'inscrire' => $prochain['id'],
                'url' => route('espace-membre.evenements', ['evenement' => $prochain['id']]),
            ];
        }

        // 7. Séance de mentorat à venir (confirmée ou en attente de l'autre).
        if ($seance) {
            $actions[] = [
                'cle' => 'mentorat', 'icon' => 'hand-heart',
                'titre' => 'Séance de mentorat '.Quand::delai($seance['debut']),
                'detail' => ($seance['je_suis'] === 'mentor' ? 'Avec votre mentoré·e ' : 'Avec votre mentor ').$seance['avec'].' · '.($seance['format'] === 'visio' ? 'visio' : 'présentiel').'.',
                'bouton' => 'Voir la séance',
                'url' => route('espace-membre.mentorat.suivi', $seance['mentorship_id']),
            ];
        }

        // 8. Aucune formation : en commencer une.
        if ($aCommencer) {
            $actions[] = [
                'cle' => 'formation', 'icon' => 'graduation-cap',
                'titre' => 'Commencer une formation',
                'detail' => 'Par exemple : '.$aCommencer['titre'].' ('.$aCommencer['detail'].').',
                'bouton' => 'Découvrir la formation',
                'url' => $aCommencer['url'],
            ];
        }

        return array_slice($actions, 0, 3);
    }
}
