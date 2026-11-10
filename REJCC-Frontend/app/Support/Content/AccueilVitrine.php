<?php

namespace App\Support\Content;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Données de la page d'accueil de la vitrine (resources/views/pages/home.blade.php).
 * Chaque section reste masquable et ses textes modifiables depuis
 * l'administration (Pages du site → Accueil).
 */
class AccueilVitrine
{
    public static function donnees(): array
    {
        $home = Api::get('/home-content');

        $evenements = collect(Api::get('/public-events')['events'] ?? [])
            ->map(fn ($e) => ['date' => Carbon::parse($e['starts_at'])] + $e)
            // Uniquement les prochains rendez-vous (ni passés ni annulés).
            ->filter(fn ($e) => ! ($e['passe'] ?? false) && ($e['statut'] ?? 'publie') === 'publie')
            ->sortBy('date')
            ->take(6)
            ->map(fn ($e) => [
                'titre' => $e['title'],
                'date' => $e['date'],
                'lieu' => ($e['en_ligne'] ?? false) ? 'En ligne' : ($e['location'] ?? ''),
                'categorie' => $e['category'] ?? 'Événement',
                'lien' => '/evenements/'.$e['slug'],
            ])
            ->values();

        // Les 33 domaines d'activité : sous-rubriques des secteurs.
        $domaines = collect(Api::get('/sectors')['sectors'] ?? [])
            ->flatMap(fn ($s) => $s['items'] ?? [])
            ->filter()
            ->values();

        return [
            'site' => SiteConfig::get(),
            'stats' => collect($home['stats'] ?? [])->values(),
            'valeurs' => collect($home['values'] ?? [])->values(),
            'etapes' => collect($home['membership_steps'] ?? [])->values(),
            'evenements' => $evenements,
            'domaines' => $domaines,
            'portraits' => self::portraits(),
            'slides' => ['01', '05', '08', '02'],
            'actions' => [
                ['titre' => 'Rejoindre le réseau', 'texte' => "Votre demande d'adhésion en quelques minutes.", 'lien' => url('/adhesion')],
                ['titre' => 'Mon espace membre', 'texte' => 'Formations, Communauté, événements, messages.', 'lien' => route('login')],
                ['titre' => 'Découvrir CathoImpact', 'texte' => 'Des parcours qui inspirent, et peut-être le vôtre.', 'lien' => route('cathoimpact')],
            ],
            'mots' => ['entreprendre', 'grandir', 'servir', 'réussir'],
        ];
    }

    /**
     * Les 3 derniers portraits publiés de la galerie CathoImpact (gérée par la
     * Com). Cache court ; une réponse en erreur n'est pas mise en cache pour ne
     * pas masquer la galerie 5 minutes.
     */
    private static function portraits()
    {
        $portraits = Cache::get('cathoimpact-portraits');
        if ($portraits === null) {
            $r = Api::get('/cathoimpact/portraits');
            $portraits = $r['portraits'] ?? [];
            if ($r['ok'] ?? false) {
                Cache::put('cathoimpact-portraits', $portraits, 300);
            }
        }

        return collect($portraits)
            ->filter(fn ($p) => preg_match('#^https?://#i', $p['lien'] ?? '') && preg_match('#^https?://#i', $p['image_url'] ?? ''))
            ->sortByDesc('id')
            ->take(3)
            ->values();
    }

    /** Petite illustration animée d'un chiffre clé, d'après son libellé. */
    public static function visuel(string $libelle): string
    {
        $l = mb_strtolower($libelle);

        return match (true) {
            str_contains($l, 'membre') => 'points',
            str_contains($l, 'domaine') || str_contains($l, 'secteur') => 'domaines',
            str_contains($l, 'événement') || str_contains($l, 'evenement') => 'calendrier',
            default => 'cercles',
        };
    }
}
