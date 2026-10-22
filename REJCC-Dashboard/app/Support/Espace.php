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

    /** Icône illustrée de chaque département (x-ui.illustration). */
    public const ILLUSTRATIONS = [
        'tresorerie' => 'argent', 'juridique' => 'balance', 'comptable' => 'cahier', 'informatique' => 'ordinateur',
        'secretariat' => 'dossier', 'communication' => 'megaphone', 'projets' => 'etoile', 'formation' => 'livre',
        'relations-exterieures' => 'mains', 'spiritualite' => 'colombe', 'organisation' => 'agenda', 'mentorat' => 'equipe',
    ];

    /** Icône illustrée des rubriques d'administration, par route. */
    public const ILLUSTRATIONS_RUBRIQUES = [
        'admin.formations' => 'livre', 'admin.lives' => 'visio', 'admin.formateurs' => 'equipe', 'admin.parcours' => 'etoile', 'admin.certificats' => 'diplome',
        'admin.evenements' => 'agenda', 'admin.actualites' => 'megaphone', 'admin.pages' => 'globe', 'admin.contenu' => 'crayon',
        'admin.legal' => 'balance', 'admin.mediatheque' => 'dossier', 'admin.reglages' => 'horloge', 'admin.documents' => 'dossier',
        'admin.members' => 'equipe', 'admin.adhesions' => 'cahier', 'admin.abonnements' => 'argent', 'admin.mentors' => 'equipe',
        'admin.groupes' => 'mains', 'admin.projets' => 'etoile', 'admin.marketplace' => 'argent', 'admin.emplois' => 'dossier',
        'admin.contacts' => 'megaphone', 'admin.signalements' => 'horloge', 'admin.partenariats' => 'mains',
        'admin.notifications' => 'megaphone', 'admin.audit' => 'cahier', 'admin.suggestions' => 'crayon', 'admin.newsletter' => 'megaphone',
    ];

    /** Page d'arrivée : l'espace du département, sinon l'administration. */
    public static function accueil(?array $user): string
    {
        if (! self::autorise($user)) {
            return route('login');
        }

        return ($slug = self::departement($user)) ? route('departements.show', $slug) : route('admin.dashboard');
    }
}
