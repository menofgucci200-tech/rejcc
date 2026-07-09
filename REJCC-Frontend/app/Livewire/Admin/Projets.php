<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Projets extends Component
{
    public array $statuts = ['attente', 'attente', 'attente'];

    public function valider(int $index): void
    {
        $this->statuts[$index] = 'valide';
    }

    public function rejeter(int $index): void
    {
        $this->statuts[$index] = 'rejete';
    }

    protected function data(): array
    {
        return [
            ['titre' => 'Marché numérique du Nord', 'membres' => 5, 'description' => "Plateforme de vente groupée pour artisans de la région de Korhogo.", 'objectif' => '4 500 000'],
            ['titre' => 'Coopérative de transformation cacao', 'membres' => 7, 'description' => "Unité de transformation locale pour valoriser la production de cacao ivoirien.", 'objectif' => '12 000 000'],
            ['titre' => 'École du code REJCC', 'membres' => 4, 'description' => "Programme de formation accélérée au développement web pour jeunes déscolarisés.", 'objectif' => '3 000 000'],
        ];
    }

    protected function suivi(): array
    {
        return [
            ['titre' => 'AgriConnect CI', 'leve' => '4 200 000', 'pct' => 53],
            ['titre' => 'Boutique solidaire', 'leve' => '900 000', 'pct' => 26],
            ['titre' => 'App de mentorat REJCC', 'leve' => '2 000 000', 'pct' => 100],
        ];
    }

    public function render()
    {
        $projets = array_map(function ($p, $i) {
            $s = $this->statuts[$i];
            $info = $s === 'valide' ? ['#22A85A', '#EAF6EE', 'Validé'] : ($s === 'rejete' ? ['#AC0100', '#F9E9E9', 'Rejeté'] : ['#F5A623', '#FCF1DD', 'En attente']);
            $p['index'] = $i;
            $p['statutColor'] = $info[0];
            $p['statutBg'] = $info[1];
            $p['statutLabel'] = $info[2];
            $p['enAttente'] = $s === 'attente';

            return $p;
        }, $this->data(), array_keys($this->data()));

        return view('livewire.admin.projets', ['projets' => $projets, 'suivi' => $this->suivi()]);
    }
}
