<?php

namespace App\Livewire\Admin;

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

        $fonds = (int) ($stats['fonds_incubateur'] ?? 0);
        $fondsLabel = $fonds >= 1_000_000
            ? number_format($fonds / 1_000_000, 1, ',', ' ').' M'
            : number_format($fonds, 0, ',', ' ');

        $cards = [
            ['icon' => 'users', 'label' => 'Membres', 'value' => $stats['membres'] ?? 0, 'sub' => ($stats['mentors'] ?? 0).' mentor(s)', 'subColor' => '#4F6FBF'],
            ['icon' => 'graduation-cap', 'label' => 'Formations publiées', 'value' => $stats['formations'] ?? 0, 'sub' => 'au catalogue', 'subColor' => '#4F6FBF'],
            ['icon' => 'award', 'label' => 'Certificats délivrés', 'value' => $stats['certificats'] ?? 0, 'sub' => 'formations certifiantes', 'subColor' => '#22A85A'],
            ['icon' => 'gem', 'label' => 'Fonds levés (incubateur)', 'value' => $fondsLabel, 'sub' => 'FCFA cumulés', 'subColor' => '#5B677A'],
        ];

        $enAttente = array_values(array_filter([
            ($stats['candidatures_attente'] ?? 0) > 0 ? ['texte' => ($stats['candidatures_attente']).' demande(s) d\'adhésion en attente de traitement', 'dot' => '#F5A623', 'route' => 'admin.adhesions'] : null,
            ($stats['non_traites'] ?? 0) > 0 ? ['texte' => ($stats['non_traites']).' message(s) de contact non traités', 'dot' => '#AC0100', 'route' => 'admin.contacts'] : null,
            ($stats['partenariats_attente'] ?? 0) > 0 ? ['texte' => ($stats['partenariats_attente']).' demande(s) de partenariat à étudier', 'dot' => '#4F6FBF', 'route' => 'admin.partenariats'] : null,
        ]));

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

        return view('livewire.admin.dashboard', [
            'cards' => $cards,
            'mois' => $mois,
            'enAttente' => $enAttente,
            'parcoursRepartition' => $parcoursRepartition,
        ]);
    }
}
