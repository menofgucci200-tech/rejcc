<?php

namespace App\Livewire\Member;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Communaute extends Component
{
    protected function discussions(): array
    {
        return [
            ['titre' => 'Comment structurer une levée de fonds pour une petite association ?', 'auteur' => 'Grace Nkolo', 'reponses' => 12],
            ['titre' => 'Vos meilleures pratiques pour concilier études et projet entrepreneurial', 'auteur' => 'Éric Mvondo', 'reponses' => 8],
            ['titre' => 'Retour d\'expérience : mon premier stand au salon de l\'entrepreneuriat', 'auteur' => 'Diane Ateba', 'reponses' => 5],
            ['titre' => 'Ressources pour approfondir sa lecture biblique sur le leadership', 'auteur' => 'Pasteur Jonas Ekomi', 'reponses' => 19],
            ['titre' => 'Qui serait partant pour un groupe de prière entrepreneurs le mercredi ?', 'auteur' => 'Samuel Biya', 'reponses' => 23],
        ];
    }

    protected function projets(): array
    {
        return [
            ['titre' => 'Coopérative agricole jeunesse', 'statut' => 'En recherche de partenaires'],
            ['titre' => 'Application de suivi de discipulat', 'statut' => 'En développement'],
            ['titre' => 'Atelier couture solidaire', 'statut' => 'Lancé'],
        ];
    }

    public function render()
    {
        return view('livewire.member.communaute', [
            'discussions' => $this->discussions(),
            'projets' => $this->projets(),
        ]);
    }
}
