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
            ['label' => 'Abonnements', 'icon' => 'credit-card', 'route' => 'admin.abonnements'],
            ['label' => 'Mentorat', 'icon' => 'hand-heart', 'route' => 'admin.mentors'],
        ]],
        ['label' => 'Activité réseau', 'icon' => 'network', 'items' => [
            ['label' => 'Communauté', 'icon' => 'heart-handshake', 'route' => 'admin.communaute'],
            ['label' => 'Groupes sectoriels', 'icon' => 'network', 'route' => 'admin.groupes'],
            ['label' => 'Formations', 'icon' => 'graduation-cap', 'route' => 'admin.formations'],
            ['label' => 'Formations live', 'icon' => 'calendar-days', 'route' => 'admin.lives'],
            ['label' => 'Formateurs', 'icon' => 'users', 'route' => 'admin.formateurs'],
            ['label' => 'Parcours', 'icon' => 'nav-route', 'route' => 'admin.parcours'],
            ['label' => 'Certificats', 'icon' => 'award', 'route' => 'admin.certificats'],
            ['label' => 'Événements', 'icon' => 'calendar-days', 'route' => 'admin.evenements'],
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
        ['label' => 'Communication', 'icon' => 'megaphone', 'items' => [
            // Ordre de travail de la Com : planifier, traiter, produire, puis les ressources et la mesure.
            // « Ressources » et « Statistiques » regroupent plusieurs pages (barre d'onglets commune,
            // voir ONGLETS) : l'entrée du menu est active sur chacune d'elles.
            ['label' => 'Calendrier', 'icon' => 'calendar-days', 'route' => 'admin.calendrier-editorial'],
            ['label' => 'Demandes', 'icon' => 'send', 'route' => 'admin.demandes-communication'],
            ['label' => 'CathoImpact', 'icon' => 'sparkles', 'route' => 'admin.cathoimpact'],
            ['label' => 'Créer un visuel', 'icon' => 'image', 'route' => 'admin.visuels'],
            ['label' => 'Ressources', 'icon' => 'folder-open', 'route' => 'admin.textes', 'actif' => ['admin.textes', 'admin.marque', 'admin.contacts-medias']],
            ['label' => 'Statistiques', 'icon' => 'bar-chart', 'route' => 'admin.statistiques-site', 'actif' => ['admin.statistiques-site', 'admin.liens-suivi', 'admin.bilan-communication']],
            // « Mes demandes de communication » n'est plus dans le menu : bouton
            // « Demander une communication » de la barre du haut (comptes hors Com).
        ]],
        ['label' => 'Support & système', 'icon' => 'settings', 'items' => [
            ['label' => 'Contacts', 'icon' => 'message-circle', 'route' => 'admin.contacts'],
            ['label' => 'Signalements', 'icon' => 'alert-circle', 'route' => 'admin.signalements'],
            ['label' => 'Partenariats', 'icon' => 'heart-handshake', 'route' => 'admin.partenariats'],
            ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'admin.notifications'],
            ['label' => "Journal d'audit", 'icon' => 'clock', 'route' => 'admin.audit'],
            ['label' => 'Retours des testeurs', 'icon' => 'lightbulb', 'route' => 'admin.suggestions'],
        ]],
    ];

    /** Pages hors menu, rattachées à une rubrique pour le fil d'Ariane. */
    private const RATTACHEMENTS = [
        'admin.apprenants' => ['Activité réseau', 'Formations', 'admin.formations'],
        'admin.inscription' => ['Membres', 'Comptes membres', 'admin.members'],
        'admin.evenements.pointage' => ['Activité réseau', 'Événements', 'admin.evenements'],
        // Onglets de « Ressources » et de « Statistiques » (la première page porte l'entrée du menu).
        'admin.marque' => ['Communication', 'Ressources', 'admin.textes'],
        'admin.contacts-medias' => ['Communication', 'Ressources', 'admin.textes'],
        'admin.liens-suivi' => ['Communication', 'Statistiques', 'admin.statistiques-site'],
        'admin.bilan-communication' => ['Communication', 'Statistiques', 'admin.statistiques-site'],
    ];

    /**
     * Pages regroupées sous une seule entrée du menu, affichées avec une barre
     * d'onglets commune (x-admin-light.onglets). Chaque onglet garde sa route :
     * les liens des e-mails et les favoris continuent de marcher.
     */
    public const ONGLETS = [
        'ressources' => [
            ['label' => 'Textes prêts à copier', 'icon' => 'file-text', 'route' => 'admin.textes'],
            ['label' => 'Boîte à outils de marque', 'icon' => 'gem', 'route' => 'admin.marque'],
            ['label' => 'Contacts médias', 'icon' => 'id-card', 'route' => 'admin.contacts-medias'],
        ],
        'statistiques' => [
            ['label' => 'Statistiques du site', 'icon' => 'globe', 'route' => 'admin.statistiques-site'],
            ['label' => 'Liens de suivi', 'icon' => 'link', 'route' => 'admin.liens-suivi'],
            ['label' => 'Bilan mensuel', 'icon' => 'list-checks', 'route' => 'admin.bilan-communication'],
        ],
    ];

    /**
     * Ordre des groupes du menu par département du Bureau : le groupe de son
     * travail d'abord. Les groupes non cités suivent, dans l'ordre de GROUPES.
     * Les autres comptes gardent l'ordre de GROUPES (leurs permissions
     * masquent déjà les groupes qui ne les concernent pas).
     */
    public const ORDRE_GROUPES = [
        'communication' => ['Communication', 'Activité réseau', 'Contenu du site'],
    ];

    /**
     * Entrées retirées du MENU d'un département (pas de ses droits : les
     * pages restent ouvertes par leur adresse). La Com garde la permission
     * « contenu » pour les Réglages du site, sans Pages, Blocs ni Pages légales.
     */
    public const MASQUES = [
        'communication' => ['admin.pages', 'admin.contenu', 'admin.legal'],
    ];

    /** Routes sur lesquelles une entrée du menu est active (elle-même et ses onglets). */
    public static function routesActives(array $item): array
    {
        return $item['actif'] ?? [$item['route']];
    }

    /** Groupes visibles par l'admin connecté (sections autorisées, entrées masquées du département retirées). */
    public static function groupes(): array
    {
        $user = session('api_user');
        $departement = Espace::departement($user);
        $masques = self::MASQUES[$departement] ?? [];
        $ordre = array_flip(self::ORDRE_GROUPES[$departement] ?? []);

        return collect(self::GROUPES)
            ->map(function (array $g) use ($user, $masques) {
                $g['items'] = array_values(array_filter($g['items'], fn ($i) => AdminSections::allowedRoute($user, $i['route']) && ! in_array($i['route'], $masques, true)));

                return $g;
            })
            ->filter(fn ($g) => $g['items'] !== [])
            // Tri stable : groupes du département dans son ordre, puis les autres dans l'ordre habituel.
            ->sortBy(fn ($g, $i) => ($ordre[$g['label']] ?? 100) * 1000 + $i)
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
