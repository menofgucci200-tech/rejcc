<?php

namespace App\Support;

/**
 * Sections du dashboard admin. Les slugs correspondent aux permissions
 * appliquées par l'API (middleware auth.admin:<slug>) : un administrateur
 * dont `permissions` est null a accès à tout, sinon uniquement aux slugs listés.
 */
class AdminSections
{
    /** slug de permission => libellé humain (pour les cases à cocher). */
    public const SECTIONS = [
        'adhesions' => 'Adhésions & candidatures',
        'membres' => 'Membres & inscriptions',
        'formations' => 'Formations',
        'evenements' => 'Événements',
        'projets' => 'Projets & Incubateur',
        'communaute' => 'Communauté',
        'ressources' => 'Ressources',
        'certificats' => 'Certificats',
        'opportunites' => 'Opportunités',
        'mentors' => 'Mentors',
        'actualites' => 'Actualités',
        'contenu' => 'Contenu du site',
        'newsletter' => 'Newsletter',
        'documents' => 'Documents',
        'contacts' => 'Contacts',
        'partenariats' => 'Partenariats',
        'notifications' => 'Notifications',
        'audit' => "Journal d'audit",
    ];

    /** nom de route web => slug de permission requis. */
    public const ROUTES = [
        'admin.adhesions' => 'adhesions',
        'admin.members' => 'membres',
        'admin.inscription' => 'membres',
        'admin.formations' => 'formations',
        'admin.evenements' => 'evenements',
        'admin.projets' => 'projets',
        'admin.communaute' => 'communaute',
        'admin.ressources' => 'ressources',
        'admin.certificats' => 'certificats',
        'admin.emplois' => 'opportunites',
        'admin.mentors' => 'mentors',
        'admin.actualites' => 'actualites',
        'admin.contenu' => 'contenu',
        'admin.pages' => 'contenu',
        'admin.reglages' => 'contenu',
        'admin.newsletter' => 'newsletter',
        'admin.documents' => 'documents',
        'admin.contacts' => 'contacts',
        'admin.partenariats' => 'partenariats',
        'admin.notifications' => 'notifications',
        'admin.audit' => 'audit',
    ];

    /** L'utilisateur (tableau de session api_user) a-t-il accès à cette section ? */
    public static function allowed(?array $user, string $section): bool
    {
        $permissions = $user['permissions'] ?? null;

        return $permissions === null || in_array($section, $permissions, true);
    }

    public static function allowedRoute(?array $user, string $routeName): bool
    {
        $section = self::ROUTES[$routeName] ?? null;

        return $section === null || self::allowed($user, $section);
    }
}
