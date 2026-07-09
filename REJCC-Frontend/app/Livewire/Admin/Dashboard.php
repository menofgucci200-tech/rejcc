<?php

namespace App\Livewire\Admin;

use App\Support\Api;
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

    protected function croissance(): array
    {
        $data = [
            ['v' => 520, 'l' => 'Août'], ['v' => 560, 'l' => 'Sept'], ['v' => 610, 'l' => 'Oct'], ['v' => 670, 'l' => 'Nov'],
            ['v' => 720, 'l' => 'Déc'], ['v' => 780, 'l' => 'Jan'], ['v' => 820, 'l' => 'Fév'], ['v' => 910, 'l' => 'Mar'],
            ['v' => 980, 'l' => 'Avr'], ['v' => 1050, 'l' => 'Mai'], ['v' => 1160, 'l' => 'Juin'], ['v' => 1284, 'l' => 'Juil'],
        ];
        $slice = array_slice($data, count($data) - $this->periode);
        $max = max(array_column($slice, 'v'));

        return array_map(fn ($d) => ['valeur' => $d['v'], 'label' => $d['l'], 'h' => round($d['v'] / $max * 130)], $slice);
    }

    public function render()
    {
        $stats = Api::get('/admin/stats', [], Api::token())['stats'] ?? [];

        $cards = [
            ['icon' => 'users', 'label' => 'Membres actifs', 'value' => $stats['membres'] ?? 0, 'sub' => '▲ 6,2 % ce mois', 'subColor' => '#22A85A'],
            ['icon' => 'graduation-cap', 'label' => 'Formations actives', 'value' => 32, 'sub' => '4 nouvelles ce mois', 'subColor' => '#4F6FBF'],
            ['icon' => 'award', 'label' => 'Certificats délivrés', 'value' => 918, 'sub' => '▲ 12 % ce mois', 'subColor' => '#22A85A'],
            ['icon' => 'gem', 'label' => 'Fonds levés (incubateur)', 'value' => '7,1 M', 'sub' => 'FCFA cumulés', 'subColor' => '#5B677A'],
        ];

        $enAttente = array_filter([
            ($stats['candidatures_attente'] ?? 0) > 0 ? ['texte' => ($stats['candidatures_attente']).' candidature(s) en attente de traitement', 'dot' => '#F5A623', 'route' => 'admin.adhesions'] : null,
            ($stats['adhesions_attente'] ?? 0) > 0 ? ['texte' => ($stats['adhesions_attente']).' demande(s) d\'adhésion en attente', 'dot' => '#F5A623', 'route' => 'admin.adhesions'] : null,
            ($stats['non_traites'] ?? 0) > 0 ? ['texte' => ($stats['non_traites']).' message(s) de contact non traités', 'dot' => '#AC0100', 'route' => 'admin.contacts'] : null,
            ($stats['partenariats_attente'] ?? 0) > 0 ? ['texte' => ($stats['partenariats_attente']).' demande(s) de partenariat à étudier', 'dot' => '#4F6FBF', 'route' => 'admin.partenariats'] : null,
            ['texte' => '3 nouveaux projets à valider pour l\'incubateur', 'dot' => '#AC0100', 'route' => 'admin.projets'],
            ['texte' => '2 offres d\'emploi partenaires à approuver', 'dot' => '#4F6FBF', 'route' => 'admin.emplois'],
            ['texte' => '4 demandes de mentorat non assignées', 'dot' => '#4F6FBF', 'route' => 'admin.mentors'],
        ]);

        $parcoursRepartition = [
            ['nom' => 'Entrepreneuriat', 'membres' => 412, 'pct' => 100, 'color' => 'linear-gradient(90deg,#031D59,#4F6FBF)'],
            ['nom' => 'Leadership chrétien', 'membres' => 356, 'pct' => 86, 'color' => 'linear-gradient(90deg,#AC0100,#D95B5A)'],
            ['nom' => 'Finance', 'membres' => 298, 'pct' => 72, 'color' => 'linear-gradient(90deg,#4F6FBF,#8FB0FF)'],
            ['nom' => 'Marketing', 'membres' => 241, 'pct' => 58, 'color' => 'linear-gradient(90deg,#22A85A,#5BC98A)'],
            ['nom' => 'Développement Web', 'membres' => 187, 'pct' => 45, 'color' => 'linear-gradient(90deg,#031D59,#4F6FBF)'],
            ['nom' => 'Intelligence Artificielle', 'membres' => 96, 'pct' => 23, 'color' => 'linear-gradient(90deg,#AC0100,#D95B5A)'],
        ];

        return view('livewire.admin.dashboard', [
            'cards' => $cards,
            'mois' => $this->croissance(),
            'enAttente' => array_values($enAttente),
            'parcoursRepartition' => $parcoursRepartition,
        ]);
    }
}
