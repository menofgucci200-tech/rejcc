<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Formations extends Component
{
    public array $publiees = [true, true, true, true, false, true, true, true];

    public function togglePublication(int $index): void
    {
        $this->publiees[$index] = ! $this->publiees[$index];
    }

    protected function data(): array
    {
        return [
            ['titre' => "Fondamentaux de l'entrepreneuriat", 'categorie' => 'Entrepreneuriat', 'duree' => '6 h', 'inscrits' => 412, 'visuel' => 'repeating-linear-gradient(45deg,#DCE4F5 0 12px,#C9D6F0 12px 24px)'],
            ['titre' => 'Diriger avec intégrité', 'categorie' => 'Leadership chrétien', 'duree' => '9 h', 'inscrits' => 356, 'visuel' => 'repeating-linear-gradient(-45deg,#F5DCDC 0 12px,#F0C9C9 12px 24px)'],
            ['titre' => 'Lire un bilan comptable', 'categorie' => 'Finance', 'duree' => '5 h', 'inscrits' => 298, 'visuel' => 'repeating-linear-gradient(90deg,#DCEEF5 0 12px,#C9E2F0 12px 24px)'],
            ['titre' => 'Marketing digital : les bases', 'categorie' => 'Marketing', 'duree' => '6 h', 'inscrits' => 241, 'visuel' => 'repeating-linear-gradient(45deg,#DDF5DC 0 12px,#CBF0C9 12px 24px)'],
            ['titre' => 'Créer son premier site web', 'categorie' => 'Développement Web', 'duree' => '20 h', 'inscrits' => 187, 'visuel' => 'repeating-linear-gradient(-45deg,#DCE4F5 0 12px,#C9D6F0 12px 24px)'],
            ['titre' => "Introduction à l'IA générative", 'categorie' => 'Intelligence Artificielle', 'duree' => '8 h', 'inscrits' => 96, 'visuel' => 'repeating-linear-gradient(90deg,#F5DCDC 0 12px,#F0C9C9 12px 24px)'],
            ['titre' => 'Parler en public avec assurance', 'categorie' => 'Communication', 'duree' => '4 h', 'inscrits' => 154, 'visuel' => 'repeating-linear-gradient(45deg,#DCEEF5 0 12px,#C9E2F0 12px 24px)'],
            ['titre' => 'Piloter un projet de A à Z', 'categorie' => 'Gestion de projet', 'duree' => '10 h', 'inscrits' => 120, 'visuel' => 'repeating-linear-gradient(-45deg,#DDF5DC 0 12px,#CBF0C9 12px 24px)'],
        ];
    }

    public function render()
    {
        $formations = array_map(function ($f, $i) {
            $publiee = $this->publiees[$i];
            $f['index'] = $i;
            $f['publiee'] = $publiee;

            return $f;
        }, $this->data(), array_keys($this->data()));

        return view('livewire.admin.formations', ['formations' => $formations]);
    }
}
