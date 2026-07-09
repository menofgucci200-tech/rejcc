<?php

namespace App\Livewire\Member;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Projets extends Component
{
    protected function projets(): array
    {
        return [
            [
                'titre' => 'Coopérative agricole jeunesse',
                'statut' => 'Recherche partenaires',
                'statutColor' => '#F5A623',
                'membres' => 6,
                'description' => 'Structurer un circuit court de vente de produits maraîchers cultivés par de jeunes agriculteurs chrétiens.',
            ],
            [
                'titre' => 'Application de suivi de discipulat',
                'statut' => 'En développement',
                'statutColor' => '#4F6FBF',
                'membres' => 4,
                'description' => 'Outil numérique permettant aux responsables de cellule de suivre la croissance spirituelle des membres.',
            ],
            [
                'titre' => 'Atelier couture solidaire',
                'statut' => 'Lancé',
                'statutColor' => '#22A85A',
                'membres' => 9,
                'description' => 'Formation de jeunes femmes à la couture avec insertion professionnelle progressive.',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.member.projets', ['projets' => $this->projets()]);
    }
}
