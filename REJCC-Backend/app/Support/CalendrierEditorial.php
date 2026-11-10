<?php

namespace App\Support;

use App\Mail\RappelPublications;
use App\Mail\RecapPublicationsNonPubliees;
use App\Models\CommunicationPublication;
use App\Models\CommunicationTour;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calendrier éditorial du département Communication : types d'information et
 * plan de diffusion de la Com, tours de rôle par blocs de jours, et rappels
 * par e-mail (le cœur du problème : publier le jour de son tour).
 */
class CalendrierEditorial
{
    public const CANAUX = [
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'linkedin' => 'LinkedIn',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'whatsapp' => 'WhatsApp',
        'site' => 'Site',
        'email' => 'E-mail',
        'annonces' => 'Annonces (Communauté)',
    ];

    /** Réseaux sociaux au sens du plan de diffusion (WhatsApp est compté à part). */
    public const RESEAUX_SOCIAUX = ['youtube', 'tiktok', 'linkedin', 'facebook', 'instagram'];

    /**
     * Types d'information et canaux pré-cochés (plan de diffusion de la Com).
     * « tous » = tous les canaux ; « reseaux » = réseaux sociaux. Les trois
     * derniers types n'ont pas de plan : les canaux sont choisis à la main.
     */
    public const TYPES = [
        'evenement' => ['label' => 'Nouvel événement', 'canaux' => ['tous']],
        'rappel_veille' => ['label' => 'Rappel la veille', 'canaux' => ['tous']],
        'compte_rendu' => ['label' => 'Compte rendu et photos', 'canaux' => ['email', 'annonces']],
        'portrait' => ['label' => 'Portrait CathoImpact', 'canaux' => ['reseaux', 'site']],
        'formation' => ['label' => 'Formation', 'canaux' => ['site', 'email', 'whatsapp', 'annonces']],
        'emploi' => ['label' => "Offre d'emploi", 'canaux' => ['whatsapp', 'email', 'annonces']],
        'bureau' => ['label' => 'Message du Bureau', 'canaux' => ['site', 'whatsapp', 'email']],
        'partenaire' => ['label' => 'Partenaire', 'canaux' => ['site', 'email']],
        'fete_liturgique' => ['label' => 'Fête liturgique', 'canaux' => ['tous']],
        'adhesion' => ['label' => "Campagne d'adhésion", 'canaux' => ['site', 'reseaux']],
        'spirituel' => ['label' => 'Contenu spirituel', 'canaux' => []],
        'membre' => ['label' => "Mise en avant d'un membre", 'canaux' => []],
        'autre' => ['label' => 'Autre', 'canaux' => []],
    ];

    /** Formats proposés (le champ reste libre : le « style de vidéo » de leur Excel). */
    public const FORMATS = ['Vidéo courte', 'Vidéo longue', 'Carrousel', 'Visuel', 'Texte', 'Photos', 'Live', 'Story', 'Témoignage', 'Interview'];

    public const STATUTS = [
        'prevu' => 'Prévu',
        'pret' => 'Prêt',
        'publie' => 'Publié',
        'non_publie' => 'Non publié',
        'annule' => 'Annulé',
    ];

    /** Canaux pré-cochés pour un type, dans l'ordre de CANAUX. */
    public static function canauxParDefaut(string $type): array
    {
        $plan = self::TYPES[$type]['canaux'] ?? [];
        $canaux = [];
        foreach ($plan as $c) {
            $canaux = array_merge($canaux, match ($c) {
                'tous' => array_keys(self::CANAUX),
                'reseaux' => self::RESEAUX_SOCIAUX,
                default => [$c],
            });
        }

        return array_values(array_intersect(array_keys(self::CANAUX), $canaux));
    }

    /** Plan de diffusion complet (pour l'interface). */
    public static function plan(): array
    {
        return collect(self::TYPES)->mapWithKeys(fn ($t, $cle) => [$cle => self::canauxParDefaut($cle)])->all();
    }

    /** Comptes de l'équipe Communication (mêmes droits pour tous). */
    public static function equipe(): Collection
    {
        return CathoImpact::equipe();
    }

    public static function nomCompte(?User $u): ?string
    {
        return $u ? (trim("{$u->prenom} {$u->nom}") ?: $u->email) : null;
    }

    // ── Tours de rôle ───────────────────────────────────────────────────

    /** Tour qui couvre une date (le plus récent s'il y en a plusieurs). */
    public static function tourDu(Carbon|string $date): ?CommunicationTour
    {
        $d = Carbon::parse($date)->toDateString();

        return CommunicationTour::with('responsableCompte:id,prenom,nom,email')
            ->where('du', '<=', $d)->where('au', '>=', $d)->latest('id')->first();
    }

    /**
     * Découpe un mois en blocs selon le modèle de la Com : chaque bloc se
     * termine un mercredi ou un samedi (lundi→mercredi, jeudi→samedi,
     * dimanche→mercredi suivant…), au moins deux jours par bloc. Un dernier
     * bloc d'un seul jour rejoint le précédent.
     *
     * @return list<array{du: string, au: string}>
     */
    public static function blocsDuMois(int $annee, int $mois): array
    {
        $jour = Carbon::create($annee, $mois, 1)->startOfDay();
        $fin = $jour->copy()->endOfMonth()->startOfDay();
        $blocs = [];
        while ($jour->lte($fin)) {
            // Première limite (mercredi ou samedi) à partir du lendemain.
            $limite = $jour->copy()->addDay();
            while (! in_array($limite->dayOfWeek, [Carbon::WEDNESDAY, Carbon::SATURDAY], true)) {
                $limite->addDay();
            }
            if ($limite->gt($fin)) {
                $limite = $fin->copy();
            }
            $blocs[] = ['du' => $jour->toDateString(), 'au' => $limite->toDateString()];
            $jour = $limite->copy()->addDay();
        }
        $n = count($blocs);
        if ($n > 1 && $blocs[$n - 1]['du'] === $blocs[$n - 1]['au']) {
            $blocs[$n - 2]['au'] = $blocs[$n - 1]['au'];
            array_pop($blocs);
        }

        return $blocs;
    }

    /**
     * Proposition de tours pour un mois : les membres choisis alternent bloc
     * après bloc, dans l'ordre donné (modifiable avant l'enregistrement).
     *
     * @param  list<array{id?: int|null, nom?: string|null}>  $membres
     */
    public static function proposerTours(int $annee, int $mois, array $membres, int $decalage = 0): array
    {
        $membres = array_values($membres);
        if ($membres === []) {
            return [];
        }
        $tours = [];
        foreach (self::blocsDuMois($annee, $mois) as $i => $b) {
            $m = $membres[($i + $decalage) % count($membres)];
            $tours[] = $b + ['responsable_id' => $m['id'] ?? null, 'responsable' => ($m['id'] ?? null) ? null : ($m['nom'] ?? null)];
        }

        return $tours;
    }

    // ── Lecture ─────────────────────────────────────────────────────────

    /** Tours qui chevauchent une période, du plus ancien au plus récent. */
    public static function toursEntre(string $du, string $au): Collection
    {
        return CommunicationTour::with('responsableCompte:id,prenom,nom,email')
            ->where('du', '<=', $au)->where('au', '>=', $du)->orderBy('du')->orderBy('id')->get();
    }

    /** Responsable effectif : celui de la publication, sinon celui du tour du jour. */
    public static function responsable(CommunicationPublication $p, ?Collection $tours = null): array
    {
        if ($p->responsable_id && $p->responsableCompte) {
            return ['id' => $p->responsable_id, 'nom' => self::nomCompte($p->responsableCompte), 'du_tour' => false];
        }
        if (trim((string) $p->responsable) !== '') {
            return ['id' => null, 'nom' => $p->responsable, 'du_tour' => false];
        }
        $d = $p->date->toDateString();
        $tour = $tours
            ? $tours->filter(fn ($t) => $t->du->toDateString() <= $d && $t->au->toDateString() >= $d)->sortByDesc('id')->first()
            : self::tourDu($d);
        if ($tour) {
            return ['id' => $tour->responsable_id, 'nom' => $tour->nomResponsable(), 'du_tour' => true];
        }

        return ['id' => null, 'nom' => null, 'du_tour' => false];
    }

    public static function ligne(CommunicationPublication $p, ?Collection $tours = null): array
    {
        $r = self::responsable($p, $tours);

        return [
            'id' => $p->id,
            'date' => $p->date->toDateString(),
            'heure' => $p->heure,
            'titre' => $p->titre,
            'type' => $p->type,
            'type_label' => self::TYPES[$p->type]['label'] ?? $p->type,
            'format' => $p->format,
            'canaux' => array_values($p->canaux ?? []),
            'canaux_labels' => collect($p->canaux ?? [])->map(fn ($c) => self::CANAUX[$c] ?? $c)->values()->all(),
            'responsable_id' => $p->responsable_id,
            'responsable_libre' => $p->responsable,
            'responsable_effectif' => $r,
            'notes' => $p->notes,
            'statut' => $p->statut,
            'statut_label' => self::STATUTS[$p->statut] ?? $p->statut,
            'en_retard' => $p->enRetard(),
            'liens' => array_values($p->liens ?? []),
            'publie_at' => $p->publie_at?->toIso8601String(),
            'demande_id' => $p->demande_id,
        ];
    }

    public static function ligneTour(CommunicationTour $t): array
    {
        return [
            'id' => $t->id,
            'du' => $t->du->toDateString(),
            'au' => $t->au->toDateString(),
            'responsable_id' => $t->responsable_id,
            'responsable_libre' => $t->responsable,
            'responsable' => $t->nomResponsable(),
        ];
    }

    // ── Rappels ─────────────────────────────────────────────────────────

    /**
     * Rappels par e-mail, vérifiés toutes les 10 minutes (un passage manqué du
     * cron est rattrapé au suivant) ; chaque envoi est marqué, sans doublon :
     *  - à partir de 18 h, au responsable : ses publications du lendemain ;
     *  - à partir de 8 h, au responsable : ses publications du jour ;
     *  - à partir de 9 h, à l'équipe : les publications de la veille restées non
     *    publiées (rien si tout est publié).
     *
     * @return array{veille: int, jour: int, recap: int} nombre d'e-mails envoyés
     */
    public static function envoyerRappels(): array
    {
        $n = ['veille' => 0, 'jour' => 0, 'recap' => 0];
        $maintenant = now();

        if ($maintenant->hour >= 18) {
            $n['veille'] = self::rappelerResponsables(today()->addDay(), 'rappel_veille_at', 'veille');
        }
        if ($maintenant->hour >= 8) {
            $n['jour'] = self::rappelerResponsables(today(), 'rappel_jour_at', 'jour');
        }
        if ($maintenant->hour >= 9) {
            $n['recap'] = self::recapNonPubliees();
        }

        return $n;
    }

    private static function rappelerResponsables(Carbon $date, string $colonne, string $moment): int
    {
        $publications = CommunicationPublication::with('responsableCompte')
            ->whereDate('date', $date->toDateString())->whereIn('statut', CommunicationPublication::EN_ATTENTE)
            ->whereNull($colonne)->where(fn ($q) => self::horsTesteurs($q))->orderByRaw('heure is null')->orderBy('heure')->orderBy('id')->get();
        if ($publications->isEmpty()) {
            return 0;
        }
        $tours = self::toursEntre($date->toDateString(), $date->toDateString());
        $comptes = User::whereIn('id', $publications->map(fn ($p) => self::responsable($p, $tours)['id'])->filter()->unique())
            ->where('is_active', true)->get()->keyBy('id');

        $envois = 0;
        foreach ($publications->groupBy(fn ($p) => self::responsable($p, $tours)['id'] ?? 0) as $id => $lot) {
            $u = $comptes[$id] ?? null;
            // Sans compte (nom libre ou aucun responsable) : rien à envoyer, le récap du lendemain en parlera.
            if (! $u || ! $u->email) {
                continue;
            }
            Mailer::send($u->email, new RappelPublications($u->prenom, $lot->values(), $moment, $date));
            CommunicationPublication::whereIn('id', $lot->pluck('id'))->update([$colonne => now()]);
            $envois++;
        }

        return $envois;
    }

    /** Les publications créées par des comptes de test ne déclenchent aucun e-mail. */
    private static function horsTesteurs($q)
    {
        return $q->whereNull('cree_par')->orWhereNotIn('cree_par', User::whereNotNull('departement')->select('id'));
    }

    private static function recapNonPubliees(): int
    {
        $hier = today()->subDay()->toDateString();
        $publications = CommunicationPublication::with('responsableCompte')
            ->whereDate('date', $hier)->whereNotIn('statut', ['publie', 'annule'])
            ->whereNull('retard_signale_at')->where(fn ($q) => self::horsTesteurs($q))->orderBy('heure')->orderBy('id')->get();
        if ($publications->isEmpty()) {
            return 0;
        }
        $tours = self::toursEntre($hier, $hier);
        $lignes = $publications->map(fn ($p) => self::ligne($p, $tours))->values();
        $destinataires = self::equipe()->pluck('email')->filter()->map(fn ($e) => mb_strtolower($e))->unique()->values();

        foreach ($destinataires as $email) {
            Mailer::send($email, new RecapPublicationsNonPubliees($lignes, Carbon::parse($hier)));
        }
        CommunicationPublication::whereIn('id', $publications->pluck('id'))->update(['retard_signale_at' => now()]);

        return $destinataires->count();
    }
}
