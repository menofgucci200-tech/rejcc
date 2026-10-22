<?php

namespace App\Support;

/**
 * Adresses du site public et de l'espace membre (rejcc.site), qui ne font
 * pas partie du dashboard : cartes des membres, inscriptions aux
 * événements, actualités, pages légales, vérification des certificats.
 */
class Site
{
    public static function url(string $path = '/'): string
    {
        return rtrim((string) config('services.site.url'), '/').'/'.ltrim($path, '/');
    }
}
