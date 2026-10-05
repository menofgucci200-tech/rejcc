<?php

namespace App\Support\Content;

use App\Support\Api;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Pages légales (mentions, CGU, confidentialité…) rédigées depuis l'admin.
 * La liste est mise en cache 5 min pour les pieds de page ; l'admin la
 * purge à chaque enregistrement.
 */
class LegalPages
{
    public const SLUGS = ['mentions-legales', 'cgu', 'politique-de-confidentialite', 'politique-cookies', 'charte-du-membre', 'conditions-abonnement'];

    /** Libellés courts pour les pieds de page. */
    public const LIBELLES_COURTS = [
        'mentions-legales' => 'Mentions légales',
        'cgu' => 'CGU',
        'politique-de-confidentialite' => 'Confidentialité',
        'politique-cookies' => 'Cookies',
        'charte-du-membre' => 'Charte du membre',
        'conditions-abonnement' => "Conditions d'abonnement",
    ];

    private const CACHE_KEY = 'legal-pages';

    /** @return array<int, array{slug: string, titre: string, court: string, url: string}> */
    public static function all(): array
    {
        $pages = Cache::remember(self::CACHE_KEY, 300, fn () => Api::get('/legal-pages')['pages'] ?? []);

        // Repli si l'API ne répond pas : les liens restent présents.
        if ($pages === []) {
            $pages = array_map(fn ($s) => ['slug' => $s, 'titre' => self::LIBELLES_COURTS[$s]], self::SLUGS);
        }

        return array_map(fn ($p) => $p + [
            'court' => self::LIBELLES_COURTS[$p['slug']] ?? $p['titre'],
            'url' => url('/'.$p['slug']),
        ], $pages);
    }

    public static function find(string $slug): ?array
    {
        $r = Api::get('/legal-pages/'.$slug);

        return ($r['ok'] ?? false) ? $r['page'] : null;
    }

    /**
     * Markdown → HTML sûr (HTML brut retiré), avec une ancre sur chaque titre
     * de niveau 2, et le sommaire correspondant.
     *
     * @return array{html: string, sommaire: array<int, array{id: string, titre: string}>}
     */
    public static function rendu(string $markdown): array
    {
        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $sommaire = [];
        $html = preg_replace_callback('#<h2>(.*?)</h2>#s', function ($m) use (&$sommaire) {
            $titre = trim(strip_tags($m[1]));
            $id = Str::slug($titre) ?: 'section-'.(count($sommaire) + 1);
            $sommaire[] = ['id' => $id, 'titre' => $titre];

            return '<h2 id="'.$id.'">'.$m[1].'</h2>';
        }, $html);

        return ['html' => $html, 'sommaire' => $sommaire];
    }

    public static function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('sitemap.xml');
    }
}
