<?php

namespace App\Livewire\Member;

use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Emplois extends Component
{
    public string $filtre = 'tous';

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    protected function offres(): array
    {
        return [
            ['poste' => 'Chargé de projet associatif', 'entreprise' => 'Fondation Espoir Jeunesse', 'lieu' => 'Yaoundé', 'type' => 'emploi', 'date' => 'Il y a 2 jours'],
            ['poste' => 'Stagiaire comptabilité', 'entreprise' => 'Cabinet Biya & Associés', 'lieu' => 'Douala', 'type' => 'stage', 'date' => 'Il y a 3 jours'],
            ['poste' => 'Développeur web junior', 'entreprise' => 'AgriTech Cameroun', 'lieu' => 'Télétravail', 'type' => 'emploi', 'date' => 'Il y a 5 jours'],
            ['poste' => 'Assistant communication', 'entreprise' => 'REJCC — Siège national', 'lieu' => 'Yaoundé', 'type' => 'stage', 'date' => 'Il y a 1 semaine'],
            ['poste' => 'Coordinateur mentorat', 'entreprise' => 'Réseau Grace Entrepreneurs', 'lieu' => 'Douala', 'type' => 'emploi', 'date' => 'Il y a 1 semaine'],
            ['poste' => 'Stagiaire marketing digital', 'entreprise' => 'Boutique Fraternité', 'lieu' => 'Télétravail', 'type' => 'stage', 'date' => 'Il y a 2 semaines'],
        ];
    }

    public function render()
    {
        $offres = Collection::make($this->offres())
            ->when($this->filtre !== 'tous', fn ($c) => $c->where('type', $this->filtre))
            ->values();

        return view('livewire.member.emplois', ['offres' => $offres]);
    }
}
