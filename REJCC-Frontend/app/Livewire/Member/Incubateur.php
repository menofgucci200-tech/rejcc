<?php

namespace App\Livewire\Member;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Incubateur extends Component
{
    protected function projets(): array
    {
        return [
            [
                'titre' => 'Coopérative agricole jeunesse',
                'statut' => 'Financement en cours',
                'statutColor' => '#F5A623',
                'leve' => 3_200_000,
                'objectif' => 8_000_000,
                'jalons' => [
                    ['label' => 'Étude de marché', 'fait' => true],
                    ['label' => 'Business plan validé', 'fait' => true],
                    ['label' => 'Levée de fonds', 'fait' => false],
                    ['label' => 'Lancement pilote', 'fait' => false],
                ],
            ],
            [
                'titre' => 'Atelier couture solidaire',
                'statut' => 'Financé',
                'statutColor' => '#22A85A',
                'leve' => 5_000_000,
                'objectif' => 5_000_000,
                'jalons' => [
                    ['label' => 'Étude de marché', 'fait' => true],
                    ['label' => 'Business plan validé', 'fait' => true],
                    ['label' => 'Levée de fonds', 'fait' => true],
                    ['label' => 'Lancement pilote', 'fait' => true],
                ],
            ],
            [
                'titre' => 'Application de suivi de discipulat',
                'statut' => 'En évaluation',
                'statutColor' => '#4F6FBF',
                'leve' => 0,
                'objectif' => 4_500_000,
                'jalons' => [
                    ['label' => 'Étude de marché', 'fait' => true],
                    ['label' => 'Business plan validé', 'fait' => false],
                    ['label' => 'Levée de fonds', 'fait' => false],
                    ['label' => 'Lancement pilote', 'fait' => false],
                ],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.member.incubateur', ['projets' => $this->projets()]);
    }
}
