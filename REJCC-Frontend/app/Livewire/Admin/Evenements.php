<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Evenements extends Component
{
    public array $annules = [false, false, false, false, false];

    public function toggle(int $index): void
    {
        $this->annules[$index] = ! $this->annules[$index];
    }

    protected function data(): array
    {
        return [
            ['jour' => '15', 'mois' => 'JUIL', 'tag' => 'ATELIER', 'tagColor' => '#AC0100', 'titre' => 'Prototyper son offre en 3 heures', 'detail' => 'Abidjan, Plateau · 14 h – 17 h', 'inscrits' => 38],
            ['jour' => '18', 'mois' => 'JUIL', 'tag' => 'NETWORKING', 'tagColor' => '#4F6FBF', 'titre' => 'Café des entrepreneurs REJCC', 'detail' => 'Cocody · 9 h – 11 h', 'inscrits' => 52],
            ['jour' => '21', 'mois' => 'JUIL', 'tag' => 'MASTERCLASS', 'tagColor' => '#031D59', 'titre' => 'Finance : lever ses premiers fonds', 'detail' => 'En ligne · 19 h', 'inscrits' => 121],
            ['jour' => '25', 'mois' => 'JUIL', 'tag' => 'CONFÉRENCE', 'tagColor' => '#AC0100', 'titre' => 'Foi et excellence en affaires', 'detail' => 'Paroisse St-Jean · 16 h', 'inscrits' => 87],
            ['jour' => '02', 'mois' => 'AOÛT', 'tag' => 'ATELIER', 'tagColor' => '#AC0100', 'titre' => 'Storytelling pour entrepreneurs', 'detail' => 'Bouaké · 10 h – 13 h', 'inscrits' => 24],
        ];
    }

    public function render()
    {
        $evenements = array_map(function ($ev, $i) {
            $annule = $this->annules[$i];
            $ev['index'] = $i;
            $ev['annule'] = $annule;

            return $ev;
        }, $this->data(), array_keys($this->data()));

        return view('livewire.admin.evenements', ['evenements' => $evenements]);
    }
}
