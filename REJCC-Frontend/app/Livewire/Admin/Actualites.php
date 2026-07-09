<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Actualites extends Component
{
    public string $titre = '';

    public string $categorie = 'Vie du réseau';

    public string $contenu = '';

    public ?string $messagePublication = null;

    public array $publies = [true, true, false, true, true];

    public function publier(): void
    {
        $this->messagePublication = 'Article publié avec succès.';
        $this->titre = '';
        $this->contenu = '';
    }

    public function toggle(int $index): void
    {
        $this->publies[$index] = ! $this->publies[$index];
    }

    protected function data(): array
    {
        return [
            ['titre' => 'Retour sur le forum entrepreneuriat 2026', 'categorie' => 'Vie du réseau', 'date' => 'il y a 3 jours'],
            ['titre' => 'Trois membres lauréats du prix jeunesse catholique', 'categorie' => 'Témoignage', 'date' => 'il y a 1 semaine'],
            ['titre' => 'Nouvelle session de l\'incubateur en septembre', 'categorie' => 'Formation', 'date' => 'brouillon'],
            ['titre' => 'Le mot de l\'aumônier national', 'categorie' => 'Vie du réseau', 'date' => 'il y a 2 semaines'],
            ['titre' => 'Bilan de la masterclass finance de juin', 'categorie' => 'Formation', 'date' => 'il y a 3 semaines'],
        ];
    }

    public function render()
    {
        $articles = array_map(function ($a, $i) {
            $publie = $this->publies[$i];
            $a['index'] = $i;
            $a['publie'] = $publie;

            return $a;
        }, $this->data(), array_keys($this->data()));

        return view('livewire.admin.actualites', ['articles' => $articles]);
    }
}
