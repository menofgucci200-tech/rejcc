<?php

namespace App\Support;

/**
 * Espaces du dashboard : l'administration générale (toutes les rubriques)
 * et l'espace de chaque département du Bureau (ses rubriques seulement).
 * Seuls les comptes d'administration s'y connectent.
 */
class Espace
{
    /** Le compte peut-il ouvrir le dashboard ? */
    public static function autorise(?array $user): bool
    {
        return ($user['role'] ?? null) === 'admin';
    }

    /** Administrateur général : ni compte de département, ni accès limité. */
    public static function adminGeneral(?array $user): bool
    {
        if (! self::autorise($user)) {
            return false;
        }

        return (bool) ($user['admin_complet'] ?? (($user['permissions'] ?? null) === null && empty($user['bureau_departement']) && empty($user['departement'])));
    }

    public static function departement(?array $user): ?string
    {
        return self::autorise($user) ? ($user['bureau_departement'] ?? null) : null;
    }

    /** Page d'arrivée : l'espace du département, sinon l'administration. */
    public static function accueil(?array $user): string
    {
        if (! self::autorise($user)) {
            return route('login');
        }

        return ($slug = self::departement($user)) ? route('departements.show', $slug) : route('admin.dashboard');
    }
}
