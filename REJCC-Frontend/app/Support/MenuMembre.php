<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Menu de l'espace membre, défini une seule fois : 8 entrées, dont trois
 * groupes (Formations, Réseau, Opportunités) dont les pages gardent leurs
 * routes et sont reliées par une barre d'onglets commune. Utilisé par le menu
 * latéral, la barre d'onglets, le fil d'Ariane et la barre du bas (téléphone).
 */
class MenuMembre
{
    /**
     * Entrées du menu pour le membre connecté. Chaque entrée a un libellé, une
     * icône, une route (pour un groupe : son premier onglet accessible), un état
     * actif, une pastille (cumul des onglets pour un groupe) et, pour un groupe,
     * ses onglets. Un onglet « verrouillé » est réservé aux abonnés.
     */
    public static function entrees(): array
    {
        $user = Api::user();
        $abonne = (bool) ($user->subscription_active ?? false);
        $compteurs = NavCompteurs::get();
        $route = RouteCourante::nom();

        $definition = [
            ['label' => 'Accueil', 'icon' => 'nav-home', 'route' => 'espace-membre.dashboard'],
            ['label' => 'Formations', 'icon' => 'graduation-cap', 'cle' => 'formations', 'onglets' => [
                ['label' => 'Mes formations', 'icon' => 'graduation-cap', 'route' => 'espace-membre.formations'],
                ['label' => 'Catalogue', 'icon' => 'nav-compass', 'route' => 'espace-membre.catalogue'],
                ['label' => 'Parcours', 'icon' => 'nav-route', 'route' => 'espace-membre.parcours'],
                ['label' => 'Certificats', 'icon' => 'award', 'route' => 'espace-membre.certificats'],
                ...(($user->est_formateur ?? false) ? [['label' => 'Mes sessions', 'icon' => 'calendar-days', 'route' => 'espace-membre.sessions-animees']] : []),
            ]],
            ['label' => 'Communauté', 'icon' => 'heart-handshake', 'route' => 'espace-membre.communaute', 'cle' => 'communaute', 'badge' => $compteurs['communaute'] ?? 0],
            ['label' => 'Réseau', 'icon' => 'users', 'cle' => 'reseau', 'onglets' => [
                ['label' => 'Annuaire', 'icon' => 'users', 'route' => 'espace-membre.directory', 'locked' => true],
                ['label' => 'Groupes sectoriels', 'icon' => 'network', 'route' => 'espace-membre.groupes', 'badge' => $compteurs['groupes'] ?? 0],
                ['label' => 'Mentorat', 'icon' => 'hand-heart', 'route' => 'espace-membre.mentorat', 'badge' => $compteurs['mentorat'] ?? 0],
            ]],
            ['label' => 'Messagerie', 'icon' => 'message-circle', 'route' => 'espace-membre.messaging', 'cle' => 'messages', 'badge' => $compteurs['messages'] ?? 0],
            ['label' => 'Événements', 'icon' => 'calendar-days', 'route' => 'espace-membre.evenements'],
            ['label' => 'Opportunités', 'icon' => 'nav-briefcase', 'cle' => 'opportunites', 'onglets' => [
                ['label' => 'Emploi & Stage', 'icon' => 'nav-briefcase', 'route' => 'espace-membre.emplois'],
                ['label' => 'Projets', 'icon' => 'nav-projects', 'route' => 'espace-membre.projets', 'locked' => true],
                ['label' => 'Marketplace', 'icon' => 'store', 'route' => 'espace-membre.marketplace'],
            ]],
            ['label' => 'Documents', 'icon' => 'folder-open', 'route' => 'espace-membre.documents'],
        ];

        // Une page est « active » sur sa route et ses pages de détail (formations.detail…).
        $estActive = fn (string $r) => Str::is($r, $route) || Str::is($r.'.*', $route);

        return array_map(function (array $e) use ($abonne, $estActive) {
            if (! isset($e['onglets'])) {
                return $e + ['active' => $estActive($e['route']), 'verrouille' => false, 'badge' => 0];
            }

            $e['onglets'] = array_map(fn (array $o) => $o + [
                'active' => $estActive($o['route']),
                'verrouille' => ($o['locked'] ?? false) && ! $abonne,
                'badge' => 0,
            ], $e['onglets']);

            // Le groupe ouvre son premier onglet accessible (un non-abonné arrive
            // sur « Groupes sectoriels » plutôt que sur l'annuaire verrouillé).
            $premier = collect($e['onglets'])->firstWhere('verrouille', false) ?? $e['onglets'][0];

            return $e + [
                'route' => $premier['route'],
                'active' => collect($e['onglets'])->contains('active', true),
                'verrouille' => false,
                'badge' => (int) collect($e['onglets'])->sum(fn ($o) => (int) $o['badge']),
            ];
        }, $definition);
    }

    /** Groupe de la page courante (avec ses onglets), ou null hors groupe. */
    public static function groupeCourant(): ?array
    {
        foreach (static::entrees() as $e) {
            if (isset($e['onglets']) && $e['active']) {
                return $e;
            }
        }

        return null;
    }

    /** Nom de la rubrique d'une route pour le fil d'Ariane (« Formations », « Réseau »…). */
    public static function rubrique(string $route): ?string
    {
        $segment = explode('.', $route)[1] ?? '';

        return match ($segment) {
            'formations', 'catalogue', 'parcours', 'certificats', 'sessions-animees' => 'Formations',
            'directory', 'groupes', 'mentorat' => 'Réseau',
            'emplois', 'projets', 'marketplace' => 'Opportunités',
            'carte', 'profile', 'abonnement', 'notifications' => 'Mon compte',
            default => null,
        };
    }
}
