<?php

namespace App\Livewire\Member;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Ressources extends Component
{
    protected function categories(): array
    {
        return [
            ['titre' => 'Ebooks', 'icon' => 'book-open', 'color' => '#4F6FBF', 'nombre' => 18],
            ['titre' => 'Modèles de business plan', 'icon' => 'file-text', 'color' => '#AC0100', 'nombre' => 6],
            ['titre' => 'Vidéos de formation', 'icon' => 'video', 'color' => '#22A85A', 'nombre' => 24],
            ['titre' => 'Podcasts', 'icon' => 'headphones', 'color' => '#F5A623', 'nombre' => 12],
            ['titre' => 'Documents pratiques', 'icon' => 'folder-open', 'color' => '#4F6FBF', 'nombre' => 30],
        ];
    }

    protected function temoignages(): array
    {
        return [
            ['nom' => 'Diane Ateba', 'role' => 'Fondatrice, Boutique Fraternité', 'citation' => 'Les ressources de l\'incubateur m\'ont permis de structurer mon business plan en quelques semaines.'],
            ['nom' => 'Éric Mvondo', 'role' => 'Développeur, AgriTech Cameroun', 'citation' => 'Le mentorat REJCC a changé ma façon de diriger une équipe technique.'],
            ['nom' => 'Grace Nkolo', 'role' => 'Entrepreneure sociale', 'citation' => 'Grâce au parcours entrepreneuriat social, j\'ai pu lever mes premiers fonds.'],
        ];
    }

    public function render()
    {
        return view('livewire.member.ressources', [
            'categories' => $this->categories(),
            'temoignages' => $this->temoignages(),
        ]);
    }
}
