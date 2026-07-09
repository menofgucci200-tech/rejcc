<?php

namespace App\Livewire\Member;

use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Catalogue extends Component
{
    public string $filtre = 'toutes';

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    protected function cours(): array
    {
        return [
            ['titre' => 'Leadership chrétien — Niveau 3', 'tag' => 'Leadership', 'tagColor' => '#4F6FBF', 'duree' => '6 semaines', 'niveau' => 'Avancé', 'from' => '#031D59', 'to' => '#4F6FBF', 'gratuit' => false, 'certifiante' => true],
            ['titre' => 'Initiation à la comptabilité', 'tag' => 'Finance', 'tagColor' => '#AC0100', 'duree' => '4 semaines', 'niveau' => 'Débutant', 'from' => '#AC0100', 'to' => '#F58B8B', 'gratuit' => true, 'certifiante' => false],
            ['titre' => 'Prise de parole en public', 'tag' => 'Communication', 'tagColor' => '#22A85A', 'duree' => '3 semaines', 'niveau' => 'Débutant', 'from' => '#22A85A', 'to' => '#7FE0A6', 'gratuit' => true, 'certifiante' => true],
            ['titre' => 'Rédiger un business plan', 'tag' => 'Entrepreneuriat', 'tagColor' => '#F5A623', 'duree' => '5 semaines', 'niveau' => 'Intermédiaire', 'from' => '#F5A623', 'to' => '#F7C873', 'gratuit' => false, 'certifiante' => true],
            ['titre' => 'Éthique chrétienne au travail', 'tag' => 'Spiritualité', 'tagColor' => '#4F6FBF', 'duree' => '2 semaines', 'niveau' => 'Tous niveaux', 'from' => '#4F6FBF', 'to' => '#8FA3D9', 'gratuit' => true, 'certifiante' => false],
            ['titre' => 'Marketing digital pour PME', 'tag' => 'Marketing', 'tagColor' => '#AC0100', 'duree' => '4 semaines', 'niveau' => 'Intermédiaire', 'from' => '#031D59', 'to' => '#AC0100', 'gratuit' => false, 'certifiante' => true],
            ['titre' => 'Gestion du temps et priorités', 'tag' => 'Productivité', 'tagColor' => '#22A85A', 'duree' => '2 semaines', 'niveau' => 'Débutant', 'from' => '#22A85A', 'to' => '#4F6FBF', 'gratuit' => true, 'certifiante' => false],
            ['titre' => 'Lever des fonds pour son projet', 'tag' => 'Entrepreneuriat', 'tagColor' => '#F5A623', 'duree' => '6 semaines', 'niveau' => 'Avancé', 'from' => '#F5A623', 'to' => '#AC0100', 'gratuit' => false, 'certifiante' => true],
        ];
    }

    public function render()
    {
        $cours = Collection::make($this->cours())
            ->when($this->filtre === 'gratuit', fn ($c) => $c->where('gratuit', true))
            ->when($this->filtre === 'certifiante', fn ($c) => $c->where('certifiante', true))
            ->values();

        return view('livewire.member.catalogue', ['cours' => $cours]);
    }
}
