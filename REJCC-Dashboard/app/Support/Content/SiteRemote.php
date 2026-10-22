<?php

namespace App\Support\Content;

use App\Support\Api;
use Illuminate\Support\Facades\Cache;

/**
 * Réglages et sections de pages pilotés depuis l'admin (API backend).
 * Un seul appel API par requête au plus (mémo statique) et cache fichier
 * 5 min pour ne pas ralentir la vitrine. L'admin purge le cache à chaque
 * enregistrement via clear().
 */
class SiteRemote
{
    private const CACHE_KEY = 'site-remote';

    private const TTL = 300;

    private static ?array $data = null;

    private static function data(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        return self::$data = Cache::remember(self::CACHE_KEY, self::TTL, function () {
            $r = Api::get('/site-settings');

            $sections = [];
            foreach ($r['sections'] ?? [] as $s) {
                $sections[$s['page'].'/'.$s['section']] = $s;
            }

            return [
                'settings' => $r['settings'] ?? [],
                'sections' => $sections,
            ];
        });
    }

    /** Valeur d'un réglage (identity.slogan, social.tiktok…), sinon défaut. */
    public static function setting(string $key, mixed $default = null): mixed
    {
        $value = self::data()['settings'][$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /** Champ d'une section de page, avec repli sur la valeur codée en dur. */
    public static function field(string $page, string $section, string $field, mixed $default = null): mixed
    {
        $content = self::data()['sections'][$page.'/'.$section]['content'] ?? null;
        $value = $content[$field] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /** La section est-elle visible ? (true si jamais configurée) */
    public static function visible(string $page, string $section): bool
    {
        return (bool) (self::data()['sections'][$page.'/'.$section]['visible'] ?? true);
    }

    /** Contenu brut d'une section (pour préremplir les formulaires admin). */
    public static function section(string $page, string $section): array
    {
        return self::data()['sections'][$page.'/'.$section] ?? ['content' => null, 'visible' => true];
    }

    /** À appeler après chaque écriture admin pour publier immédiatement. */
    public static function clear(): void
    {
        self::$data = null;
        Cache::forget(self::CACHE_KEY);
    }
}
