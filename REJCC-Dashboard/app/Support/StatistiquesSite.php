<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Audience du site public (rejcc.site), lue directement dans les fichiers
 * du jour écrits par le site (stockage privé partagé, disque `local` :
 * statistiques/visites/AAAA-MM-JJ.json, voir REJCC-Frontend App\Support\Audience).
 * Aucun appel à l'API, sauf pour les clics des liens de suivi (clicsLiens).
 *
 * « Visiteurs » = somme des visiteurs uniques de chaque jour : une personne
 * venue deux jours différents compte deux fois (pas de suivi d'un jour à l'autre,
 * c'est le prix d'une mesure sans cookie).
 *
 * Réutilisable par le cockpit de la Communication et le bilan mensuel :
 *   StatistiquesSite::mois(2026, 11)        → mois civil (comparé au mois précédent)
 *   StatistiquesSite::derniersJours(30)     → 30 derniers jours (comparés aux 30 d'avant)
 *   StatistiquesSite::periode($debut, $fin) → période libre
 *   StatistiquesSite::clicsLiens($du, $au)  → clics des liens de suivi (API)
 */
class StatistiquesSite
{
    /** Libellés lisibles des provenances les plus courantes. */
    private const SOURCES = [
        'direct' => 'Accès direct', 'google' => 'Google', 'facebook.com' => 'Facebook', 'facebook' => 'Facebook',
        'whatsapp' => 'WhatsApp', 'linkedin.com' => 'LinkedIn', 'linkedin' => 'LinkedIn', 'lnkd.in' => 'LinkedIn',
        'tiktok.com' => 'TikTok', 'tiktok' => 'TikTok', 'youtube.com' => 'YouTube', 'youtube' => 'YouTube',
        'instagram.com' => 'Instagram', 'instagram' => 'Instagram', 't.co' => 'X (Twitter)', 'bing.com' => 'Bing',
        'email' => 'E-mail', 'annonces' => 'Annonces (Communauté)', 'dashboard.rejcc.site' => 'Dashboard',
    ];

    /** Libellés des pages connues du site. */
    private const PAGES = [
        '/' => 'Accueil', '/a-propos' => 'À propos', '/activites' => 'Activités', '/domaines' => 'Domaines',
        '/evenements' => 'Événements', '/projets' => 'Projets', '/emplois' => 'Emploi & stage', '/actualites' => 'Actualités',
        '/partenaires' => 'Partenaires', '/galerie' => 'Galerie', '/adhesion' => 'Adhésion', '/contact' => 'Contact',
        '/cathoimpact' => 'CathoImpact', '/cathoimpact/questionnaire' => 'Questionnaire CathoImpact',
        '/connexion' => 'Connexion', '/suivre-ma-candidature' => 'Suivi de candidature', '/verifier' => 'Vérification de certificat',
    ];

    /**
     * Mois civil. Pour le mois en cours, la période s'arrête aujourd'hui et la
     * comparaison porte sur les mêmes jours du mois précédent.
     *
     * @return array{
     *   du: string, au: string, mois: string, jours_mesures: int,
     *   pages_vues: int, visiteurs: int,
     *   jours: array<string, array{pages_vues: int, visiteurs: int}>,
     *   pages: list<array{chemin: string, label: string, vues: int}>,
     *   sources: list<array{source: string, label: string, visites: int}>,
     *   appareils: array{telephone: int, ordinateur: int},
     *   precedent: array{du: string, au: string, pages_vues: int, visiteurs: int},
     *   evolution: array{pages_vues: ?int, visiteurs: ?int}
     * }
     */
    public static function mois(int $annee, int $mois): array
    {
        $debut = Carbon::create($annee, $mois, 1)->startOfDay();
        $fin = $debut->copy()->endOfMonth()->startOfDay();
        if ($fin->isFuture()) {
            $fin = now()->startOfDay()->max($debut);
        }
        $precedentDebut = $debut->copy()->subMonthNoOverflow();
        $precedentFin = $precedentDebut->copy()->addDays((int) $debut->diffInDays($fin))->min($precedentDebut->copy()->endOfMonth()->startOfDay());

        return ['mois' => $debut->format('Y-m')] + self::periode($debut, $fin, $precedentDebut, $precedentFin);
    }

    /** Les $n derniers jours (aujourd'hui compris), comparés aux $n jours d'avant. */
    public static function derniersJours(int $n = 30): array
    {
        $fin = now()->startOfDay();
        $debut = $fin->copy()->subDays($n - 1);

        return ['mois' => null] + self::periode($debut, $fin);
    }

    /** Période libre (dates incluses). Sans période de comparaison, celle de même durée juste avant. */
    public static function periode(Carbon $debut, Carbon $fin, ?Carbon $precedentDebut = null, ?Carbon $precedentFin = null): array
    {
        $debut = $debut->copy()->startOfDay();
        $fin = $fin->copy()->startOfDay();
        $duree = (int) $debut->diffInDays($fin) + 1;
        $precedentFin ??= $debut->copy()->subDay();
        $precedentDebut ??= $precedentFin->copy()->subDays($duree - 1);

        $total = self::cumul($debut, $fin);
        $avant = self::cumul($precedentDebut, $precedentFin);

        arsort($total['pages']);
        arsort($total['sources']);

        return [
            'du' => $debut->toDateString(),
            'au' => $fin->toDateString(),
            'jours_mesures' => $total['jours_mesures'],
            'pages_vues' => $total['pages_vues'],
            'visiteurs' => $total['visiteurs'],
            'jours' => $total['jours'],
            'pages' => collect($total['pages'])->take(15)
                ->map(fn ($n, $chemin) => ['chemin' => (string) $chemin, 'label' => self::labelPage((string) $chemin), 'vues' => $n])->values()->all(),
            // « facebook » (lien de suivi) et « facebook.com » (site d'origine) réunis sous « Facebook ».
            'sources' => collect($total['sources'])
                ->map(fn ($n, $s) => ['source' => (string) $s, 'label' => self::labelSource((string) $s), 'visites' => $n])
                ->groupBy('label')
                ->map(fn ($g, $label) => ['source' => $g->first()['source'], 'label' => (string) $label, 'visites' => $g->sum('visites')])
                ->sortByDesc('visites')->take(15)->values()->all(),
            'appareils' => $total['appareils'],
            'precedent' => [
                'du' => $precedentDebut->toDateString(), 'au' => $precedentFin->toDateString(),
                'pages_vues' => $avant['pages_vues'], 'visiteurs' => $avant['visiteurs'],
            ],
            'evolution' => [
                'pages_vues' => self::evolution($total['pages_vues'], $avant['pages_vues']),
                'visiteurs' => self::evolution($total['visiteurs'], $avant['visiteurs']),
            ],
        ];
    }

    /**
     * Clics des liens de suivi sur une période (API, rubrique « communication »).
     *
     * @return array{ok: bool, total?: int, par_jour?: array<string, int>, par_canal?: list<array>, par_lien?: list<array>}
     */
    public static function clicsLiens(Carbon $du, Carbon $au): array
    {
        return Api::get('/admin/liens-suivi/statistiques', ['du' => $du->toDateString(), 'au' => $au->toDateString()], Api::token());
    }

    /** Agrégats bruts d'une journée (sans les empreintes), ou null si rien n'a été mesuré. */
    public static function jour(string $date): ?array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }
        // Stockage partagé absent ou illisible : aucune donnée plutôt qu'une page en erreur.
        $brut = rescue(fn () => Storage::disk('local')->get('statistiques/visites/'.$date.'.json'), null, false);
        $d = $brut ? json_decode($brut, true) : null;
        if (! is_array($d)) {
            return null;
        }
        unset($d['empreintes']);

        return $d;
    }

    /** Mois proposés dans le sélecteur : du premier mois mesuré au mois en cours (13 au plus). */
    public static function moisDisponibles(): array
    {
        $fichiers = collect(rescue(fn () => Storage::disk('local')->files('statistiques/visites'), [], false))
            ->map(fn ($f) => basename($f, '.json'))->filter(fn ($j) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $j))->sort()->values();
        $premier = $fichiers->first() ? Carbon::parse($fichiers->first())->startOfMonth() : now()->startOfMonth();
        $premier = $premier->max(now()->startOfMonth()->subMonths(12));

        $liste = [];
        for ($m = now()->startOfMonth(); $m->gte($premier); $m->subMonth()) {
            $liste[$m->format('Y-m')] = ucfirst($m->locale('fr')->translatedFormat('F Y'));
        }

        return $liste;
    }

    private static function cumul(Carbon $debut, Carbon $fin): array
    {
        $c = ['jours_mesures' => 0, 'pages_vues' => 0, 'visiteurs' => 0, 'jours' => [], 'pages' => [], 'sources' => [], 'appareils' => ['telephone' => 0, 'ordinateur' => 0]];
        for ($j = $debut->copy(); $j->lte($fin); $j->addDay()) {
            $date = $j->toDateString();
            $d = self::jour($date);
            $c['jours'][$date] = ['pages_vues' => (int) ($d['pages_vues'] ?? 0), 'visiteurs' => (int) ($d['visiteurs'] ?? 0)];
            if ($d === null) {
                continue;
            }
            $c['jours_mesures']++;
            $c['pages_vues'] += (int) ($d['pages_vues'] ?? 0);
            $c['visiteurs'] += (int) ($d['visiteurs'] ?? 0);
            foreach (['pages', 'sources', 'appareils'] as $cle) {
                foreach ((array) ($d[$cle] ?? []) as $k => $n) {
                    $c[$cle][$k] = ($c[$cle][$k] ?? 0) + (int) $n;
                }
            }
        }

        return $c;
    }

    /** Évolution en % (arrondie), null s'il n'y a rien à comparer. */
    private static function evolution(int $maintenant, int $avant): ?int
    {
        return $avant > 0 ? (int) round(($maintenant - $avant) / $avant * 100) : null;
    }

    public static function labelPage(string $chemin): string
    {
        if (isset(self::PAGES[$chemin])) {
            return self::PAGES[$chemin];
        }
        $parties = explode('/', trim($chemin, '/'));
        $base = '/'.$parties[0];

        return isset(self::PAGES[$base]) && count($parties) > 1
            ? self::PAGES[$base].' › '.str_replace(['{id}', '{code}', '-'], ['fiche', 'code', ' '], implode(' › ', array_slice($parties, 1)))
            : $chemin;
    }

    public static function labelSource(string $source): string
    {
        return self::SOURCES[$source] ?? $source;
    }
}
