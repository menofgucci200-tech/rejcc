<?php

namespace App\Livewire\Admin;

use App\Support\AdminSections;
use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Dashboard extends Component
{
    public int $periode = 6;

    public function setPeriode(int $mois): void
    {
        $this->periode = $mois;
    }

    public ?string $messageAbonnements = null;

    /** Interrupteur général : abonnements obligatoires (restrictions) ou accès libre. */
    public function basculerAbonnements(bool $obligatoires): void
    {
        if (! AdminSections::allowed(session('api_user'), 'membres')) {
            return;
        }

        $result = Api::put('/admin/subscription-mode', ['enforced' => $obligatoires], Api::token());

        $this->messageAbonnements = ($result['ok'] ?? false)
            ? ($obligatoires ? 'Abonnements activés : les restrictions s\'appliquent.' : 'Abonnements désactivés : tous les membres accèdent à tout.')
            : ($result['message'] ?? 'Une erreur est survenue.');
    }

    public function render()
    {
        $stats = Api::get('/admin/stats', [], Api::token())['stats'] ?? [];

        // Croissance réelle : cumul de membres sur les 12 derniers mois (API).
        $croissance = array_slice($stats['croissance'] ?? [], -$this->periode);
        $max = max(1, ...array_map(fn ($d) => $d['v'], $croissance ?: [['v' => 1]]));
        $mois = array_map(fn ($d) => [
            'valeur' => $d['v'],
            'label' => $d['l'],
            'h' => max(4, round($d['v'] / $max * 130)),
        ], $croissance);

        $cards = [
            ['icon' => 'users', 'label' => 'Membres', 'value' => $stats['membres'] ?? 0, 'sub' => ($stats['mentors'] ?? 0).' mentor(s)', 'subColor' => '#4F6FBF'],
            ['icon' => 'graduation-cap', 'label' => 'Formations publiées', 'value' => $stats['formations'] ?? 0, 'sub' => 'au catalogue', 'subColor' => '#4F6FBF'],
            ['icon' => 'award', 'label' => 'Certificats délivrés', 'value' => $stats['certificats'] ?? 0, 'sub' => 'formations certifiantes', 'subColor' => '#22A85A'],
            ['icon' => 'nav-projects', 'label' => 'Projets proposés', 'value' => $stats['projets'] ?? 0, 'sub' => 'toutes formes confondues', 'subColor' => '#5B677A'],
        ];

        // Même source que la cloche « À traiter » (filtrée selon les permissions).
        $couleurs = ['adhesions' => '#F5A623', 'contacts' => '#AC0100', 'partenariats' => '#4F6FBF', 'marketplace' => '#22A85A', 'projets' => '#031D59', 'mentorat' => '#AC0100', 'mentorat_retard' => '#B27007'];
        \App\Support\AdminNav::oublier();
        $enAttente = collect(\App\Support\AdminNav::aTraiter()['elements'])
            ->where('nombre', '>', 0)
            ->map(fn ($e) => ['texte' => $e['libelle'].' : '.$e['nombre'], 'dot' => $couleurs[$e['cle']] ?? '#4F6FBF', 'route' => $e['route']])
            ->values()->all();

        // Répartition réelle des inscriptions par formation (top 6).
        $palette = [
            'linear-gradient(90deg,#031D59,#4F6FBF)',
            'linear-gradient(90deg,#AC0100,#D95B5A)',
            'linear-gradient(90deg,#4F6FBF,#8FB0FF)',
            'linear-gradient(90deg,#22A85A,#5BC98A)',
        ];
        $formations = Collection::make(Api::get('/admin/formations', [], Api::token())['formations'] ?? [])
            ->sortByDesc('enrollments_count')
            ->take(6)
            ->values();
        $maxInscrits = max(1, (int) ($formations->first()['enrollments_count'] ?? 0));
        $parcoursRepartition = $formations
            ->filter(fn ($f) => ($f['enrollments_count'] ?? 0) > 0)
            ->values()
            ->map(fn ($f, $i) => [
                'nom' => $f['title'],
                'membres' => (int) $f['enrollments_count'],
                'pct' => (int) round($f['enrollments_count'] / $maxInscrits * 100),
                'color' => $palette[$i % count($palette)],
            ])
            ->all();

        $modeAbonnements = Api::get('/admin/subscription-mode', [], Api::token());

        return view('livewire.admin.dashboard', [
            'abonnements' => [
                'obligatoires' => (bool) ($modeAbonnements['enforced'] ?? false),
                'membres' => (int) ($modeAbonnements['membres'] ?? 0),
                'abonnes' => (int) ($modeAbonnements['abonnes'] ?? 0),
                'depuis' => ! empty($modeAbonnements['changed_at']) ? \Carbon\Carbon::parse($modeAbonnements['changed_at'])->translatedFormat('j F Y \à H\hi') : null,
                'modifiable' => AdminSections::allowed(session('api_user'), 'membres'),
            ],
            'cards' => $cards,
            'mois' => $mois,
            'enAttente' => $enAttente,
            'parcoursRepartition' => $parcoursRepartition,
        ]);
    }
}
