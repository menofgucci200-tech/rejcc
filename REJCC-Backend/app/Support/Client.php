<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Adresse IP et navigateur de la personne derrière une requête. Le site
 * appelle l'API depuis son serveur : il transmet ces informations dans
 * X-Client-Ip / X-Client-Agent (simple information de sécurité, affichée au
 * membre dans « Appareils connectés » et « Journal de mon compte »).
 */
class Client
{
    public static function ip(Request $r): ?string
    {
        $ip = trim((string) $r->header('X-Client-Ip'));

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $r->ip();
    }

    public static function agent(Request $r): ?string
    {
        $a = trim((string) ($r->header('X-Client-Agent') ?: $r->userAgent()));

        return $a !== '' ? mb_substr($a, 0, 255) : null;
    }

    /** « Chrome sur Android », « Safari sur iPhone »… */
    public static function appareil(?string $agent): string
    {
        $a = (string) $agent;
        $os = match (true) {
            str_contains($a, 'iPhone') => 'iPhone',
            str_contains($a, 'iPad') => 'iPad',
            str_contains($a, 'Android') => 'Android',
            str_contains($a, 'Windows') => 'Windows',
            str_contains($a, 'Mac OS') => 'Mac',
            str_contains($a, 'Linux') => 'Linux',
            default => null,
        };
        $nav = match (true) {
            str_contains($a, 'Edg/') => 'Edge',
            str_contains($a, 'OPR/') || str_contains($a, 'Opera') => 'Opera',
            str_contains($a, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($a, 'Firefox/') => 'Firefox',
            str_contains($a, 'Chrome/') => 'Chrome',
            str_contains($a, 'Safari/') => 'Safari',
            default => null,
        };

        return match (true) {
            $nav && $os => "{$nav} sur {$os}",
            (bool) $nav => $nav,
            (bool) $os => $os,
            default => 'Appareil inconnu',
        };
    }
}
