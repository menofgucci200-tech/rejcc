<?php

namespace App\Livewire\Member;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Parcours extends Component
{
    protected function parcours(): array
    {
        return [
            ['titre' => 'Leadership chrétien', 'pct' => 62, 'difficulte' => 'Intermédiaire', 'duree' => '5 mois', 'mentor' => 'Pasteur Jonas Ekomi', 'tint' => '#4F6FBF', 'cert' => true],
            ['titre' => 'Entrepreneuriat social', 'pct' => 30, 'difficulte' => 'Avancé', 'duree' => '6 mois', 'mentor' => 'Grace Nkolo', 'tint' => '#AC0100', 'cert' => true],
            ['titre' => 'Finances personnelles', 'pct' => 100, 'difficulte' => 'Débutant', 'duree' => '2 mois', 'mentor' => 'Samuel Biya', 'tint' => '#22A85A', 'cert' => true],
            ['titre' => 'Communication et influence', 'pct' => 8, 'difficulte' => 'Intermédiaire', 'duree' => '3 mois', 'mentor' => 'Diane Ateba', 'tint' => '#F5A623', 'cert' => false],
            ['titre' => 'Gouvernance d\'association', 'pct' => 0, 'difficulte' => 'Avancé', 'duree' => '4 mois', 'mentor' => 'À définir', 'tint' => '#4F6FBF', 'cert' => true],
            ['titre' => 'Fondamentaux du numérique', 'pct' => 45, 'difficulte' => 'Débutant', 'duree' => '2 mois', 'mentor' => 'Éric Mvondo', 'tint' => '#22A85A', 'cert' => false],
            ['titre' => 'Discipulat et vie spirituelle', 'pct' => 90, 'difficulte' => 'Tous niveaux', 'duree' => '3 mois', 'mentor' => 'Pasteur Jonas Ekomi', 'tint' => '#AC0100', 'cert' => true],
            ['titre' => 'Gestion de projet associatif', 'pct' => 0, 'difficulte' => 'Intermédiaire', 'duree' => '4 mois', 'mentor' => 'À définir', 'tint' => '#F5A623', 'cert' => false],
        ];
    }

    public function render()
    {
        return view('livewire.member.parcours', ['parcours' => $this->parcours()]);
    }
}
