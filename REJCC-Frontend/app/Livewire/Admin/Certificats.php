<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Certificats extends Component
{
    public array $statuts = ['attente', 'attente', 'attente', 'attente', 'attente', 'attente'];

    public function emettre(int $index): void
    {
        $this->statuts[$index] = 'emis';
    }

    public function rejeter(int $index): void
    {
        $this->statuts[$index] = 'rejete';
    }

    protected function data(): array
    {
        return [
            ['membre' => 'Bertin Yao', 'formation' => "Fondamentaux de l'entrepreneuriat", 'date' => 'il y a 1 jour', 'initiales' => 'BY', 'from' => '#031D59', 'to' => '#4F6FBF'],
            ['membre' => 'Fatou Cissé', 'formation' => 'Marketing digital : les bases', 'date' => 'il y a 1 jour', 'initiales' => 'FC', 'from' => '#AC0100', 'to' => '#D95B5A'],
            ['membre' => 'Yves Kacou', 'formation' => "Introduction à l'IA générative", 'date' => 'il y a 2 jours', 'initiales' => 'YK', 'from' => '#4F6FBF', 'to' => '#8FB0FF'],
            ['membre' => 'Grace Amani', 'formation' => 'Lire un bilan comptable', 'date' => 'il y a 3 jours', 'initiales' => 'GA', 'from' => '#031D59', 'to' => '#4F6FBF'],
            ['membre' => 'Serge N\'Guessan', 'formation' => 'Créer son premier site web', 'date' => 'il y a 3 jours', 'initiales' => 'SN', 'from' => '#AC0100', 'to' => '#D95B5A'],
            ['membre' => 'Josiane Bamba', 'formation' => 'Parler en public avec assurance', 'date' => 'il y a 4 jours', 'initiales' => 'JB', 'from' => '#4F6FBF', 'to' => '#8FB0FF'],
        ];
    }

    public function render()
    {
        $demandes = array_map(function ($d, $i) {
            $s = $this->statuts[$i];
            $info = $s === 'emis' ? ['#22A85A', '#EAF6EE', 'Émis'] : ($s === 'rejete' ? ['#AC0100', '#F9E9E9', 'Rejeté'] : ['#F5A623', '#FCF1DD', 'En attente']);
            $d['index'] = $i;
            $d['statutColor'] = $info[0];
            $d['statutBg'] = $info[1];
            $d['statutLabel'] = $info[2];
            $d['enAttente'] = $s === 'attente';

            return $d;
        }, $this->data(), array_keys($this->data()));

        return view('livewire.admin.certificats', ['demandes' => $demandes]);
    }
}
