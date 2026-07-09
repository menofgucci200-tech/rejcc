<?php

namespace App\Livewire\Member;

use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Formations extends Component
{
    public string $filtre = 'tous';

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    protected function cours(): array
    {
        return [
            ['titre' => 'Leadership chrétien — Niveau 2', 'categorie' => 'Leadership', 'pct' => 75, 'etat' => 'encours', 'from' => '#031D59', 'to' => '#4F6FBF', 'detail' => 'Module 6 sur 8 · Diriger avec intégrité et discernement'],
            ['titre' => 'Gestion financière pour entrepreneurs', 'categorie' => 'Finance', 'pct' => 40, 'etat' => 'encours', 'from' => '#AC0100', 'to' => '#F58B8B', 'detail' => 'Module 2 sur 5 · Construire un budget prévisionnel'],
            ['titre' => 'Prise de parole en public', 'categorie' => 'Communication', 'pct' => 15, 'etat' => 'encours', 'from' => '#22A85A', 'to' => '#7FE0A6', 'detail' => 'Module 1 sur 6 · Vaincre le trac'],
            ['titre' => 'Fondamentaux de l\'entrepreneuriat', 'categorie' => 'Entrepreneuriat', 'pct' => 100, 'etat' => 'termine', 'from' => '#4F6FBF', 'to' => '#8FA3D9', 'detail' => 'Terminé le 12 mai 2026'],
            ['titre' => 'Éthique chrétienne au travail', 'categorie' => 'Spiritualité', 'pct' => 100, 'etat' => 'termine', 'from' => '#F5A623', 'to' => '#F7C873', 'detail' => 'Terminé le 3 mars 2026'],
        ];
    }

    public function render()
    {
        $cours = Collection::make($this->cours())
            ->when($this->filtre !== 'tous', fn ($c) => $c->where('etat', $this->filtre))
            ->values();

        return view('livewire.member.formations', [
            'cours' => $cours,
            'enCours' => Collection::make($this->cours())->firstWhere('etat', 'encours'),
        ]);
    }
}
