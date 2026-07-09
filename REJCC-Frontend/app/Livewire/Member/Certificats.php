<?php

namespace App\Livewire\Member;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Certificats extends Component
{
    protected function certificats(): array
    {
        return [
            ['titre' => 'Fondamentaux de l\'entrepreneuriat', 'date' => '12 mai 2026', 'from' => '#4F6FBF', 'to' => '#8FA3D9'],
            ['titre' => 'Éthique chrétienne au travail', 'date' => '3 mars 2026', 'from' => '#F5A623', 'to' => '#F7C873'],
            ['titre' => 'Finances personnelles', 'date' => '20 janvier 2026', 'from' => '#22A85A', 'to' => '#7FE0A6'],
        ];
    }

    public function render()
    {
        return view('livewire.member.certificats', ['certificats' => $this->certificats()]);
    }
}
