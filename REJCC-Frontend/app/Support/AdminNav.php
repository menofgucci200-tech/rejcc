<?php

namespace App\Support;

/**
 * Structure du menu d'administration (partagée par le menu latéral et le
 * fil d'Ariane de la barre du haut), filtrée selon les permissions, et
 * éléments « À traiter » (cloche + pastilles) mis en cache 30 s en session.
 */
class AdminNav
{
    public const GROUPES = [
        ['label' => 'Membres', 'icon' => 'users', 'items' => [
            ['label' => 'Adhésions', 'icon' => 'file-text', 'route' => 'admin.adhesions'],
            ['label' => 'Comptes membres', 'icon' => 'users', 'route' => 'admin.members'],
            ['label' => 'Mentorat', 'icon' => 'hand-heart', 'route' => 'admin.mentors'],
        ]],
        ['label' => 'Activité réseau', 'icon' => 'network', 'items' => [
            ['label' => 'Groupes sectoriels', 'icon' => 'network', 'route' => 'admin.groupes'],
            ['label' => 'Formations', 'icon' => 'graduation-cap', 'route' => 'admin.formations'],
            ['label' => 'Parcours', 'icon' => 'nav-route', 'route' => 'admin.parcours'],
            ['label' => 'Certificats', 'icon' => 'award', 'route' => 'admin.certificats'],
            ['label' => 'Événements', 'icon' => 'calendar-days', 'route' => 'admin.evenements'],
            ['label' => 'Inscriptions (QR)', 'icon' => 'qr-code', 'route' => 'admin.inscriptions'],
            ['label' => 'Projets', 'icon' => 'nav-projects', 'route' => 'admin.projets'],
            ['label' => 'Marketplace', 'icon' => 'store', 'route' => 'admin.marketplace'],
            ['label' => 'Emploi & Stage', 'icon' => 'nav-briefcase', 'route' => 'admin.emplois'],
        ]],
        ['label' => 'Contenu du site', 'icon' => 'layout-dashboard', 'items' => [
            ['label' => 'Actualités', 'icon' => 'file-text', 'route' => 'admin.actualites'],
            ['label' => 'Pages du site', 'icon' => 'globe', 'route' => 'admin.pages'],
            ['label' => 'Blocs de contenu', 'icon' => 'layout-dashboard', 'route' => 'admin.contenu'],
            ['label' => 'Pages légales', 'icon' => 'shield-check', 'route' => 'admin.legal'],
            ['label' => 'Médiathèque', 'icon' => 'image', 'route' => 'admin.mediatheque'],
            ['label' => 'Documents', 'icon' => 'folder-open', 'route' => 'admin.documents'],
            ['label' => 'Newsletter', 'icon' => 'send', 'route' => 'admin.newsletter'],
            ['label' => 'Réglages du site', 'icon' => 'settings', 'route' => 'admin.reglages'],
        ]],
        ['label' => 'Support & système', 'icon' => 'settings', 'items' => [
            ['label' => 'Contacts', 'icon' => 'message-circle', 'route' => 'admin.contacts'],
            ['label' => 'Signalements', 'icon' => 'alert-circle', 'route' => 'admin.signalements'],
            ['label' => 'Partenariats', 'icon' => 'heart-handshake', 'route' => 'admin.partenariats'],
            ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'admin.notifications'],
            ['label' => "Journal d'audit", 'icon' => 'clock', 'route' => 'admin.audit'],
        ]],
    ];

    /** Pages hors menu, rattachées à une rubrique pour le fil d'Ariane. */
    private const RATTACHEMENTS = [
        'admin.inscription' => ['Membres', 'Comptes membres', 'admin.members'],
    ];

    /** Groupes visibles par l'admin connecté (sections autorisées seulement). */
    public static function groupes(): array
    {
        $user = session('api_user');

        return collect(self::GROUPES)
            ->map(function (array $g) use ($user) {
                $g['items'] = array_values(array_filter($g['items'], fn ($i) => AdminSections::allowedRoute($user, $i['route'])));

                return $g;
            })
            ->filter(fn ($g) => $g['items'] !== [])
            ->values()
            ->all();
    }

    /** [rubrique, page parente (libellé, route) ou null] pour la route courante. */
    public static function fil(string $route): array
    {
        if (isset(self::RATTACHEMENTS[$route])) {
            [$groupe, $label, $r] = self::RATTACHEMENTS[$route];

            return [$groupe, [$label, $r]];
        }
        foreach (self::GROUPES as $g) {
            foreach ($g['items'] as $i) {
                if ($i['route'] === $route) {
                    return [$g['label'], null];
                }
            }
        }

        return [null, null];
    }

    /** Éléments « À traiter » de l'admin connecté. */
    public static function aTraiter(): array
    {
        $cache = session('admin_a_traiter');
        if (is_array($cache) && ($cache['at'] ?? 0) > time() - 30) {
            return $cache['data'];
        }

        $r = Api::get('/admin/a-traiter', [], Api::token());
        $data = ['elements' => $r['elements'] ?? [], 'total' => (int) ($r['total'] ?? 0)];
        session(['admin_a_traiter' => ['at' => time(), 'data' => $data]]);

        return $data;
    }

    /** Pastille par route du menu (somme des éléments rattachés). */
    public static function pastilles(): array
    {
        $parRoute = [];
        foreach (self::aTraiter()['elements'] as $e) {
            $parRoute[$e['route']] = ($parRoute[$e['route']] ?? 0) + (int) $e['nombre'];
        }

        return $parRoute;
    }

    public static function oublier(): void
    {
        session()->forget('admin_a_traiter');
    }
}
