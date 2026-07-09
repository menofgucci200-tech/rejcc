<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Ressources extends Component
{
    public array $visibles = [true, true, true, false, true];

    public function toggleVisibilite(int $index): void
    {
        $this->visibles[$index] = ! $this->visibles[$index];
    }

    protected function data(): array
    {
        return [
            ['titre' => 'Guide pratique — Créer son business plan', 'type' => 'Ebook', 'taille' => '2.4 Mo', 'telechargements' => 341, 'tint' => '#E8EDF8', 'emoji' => '📘'],
            ['titre' => 'Modèle de prévisionnel financier', 'type' => 'Modèle', 'taille' => '180 Ko', 'telechargements' => 512, 'tint' => '#F9E9E9', 'emoji' => '📊'],
            ['titre' => 'Masterclass Finance — juin 2026', 'type' => 'Vidéo', 'taille' => '640 Mo', 'telechargements' => 289, 'tint' => '#EAF6EE', 'emoji' => '🎥'],
            ['titre' => 'Podcast — Foi et entrepreneuriat, épisode 12', 'type' => 'Audio', 'taille' => '48 Mo', 'telechargements' => 156, 'tint' => '#E8EDF8', 'emoji' => '🎙️'],
            ['titre' => 'Statuts et charte du réseau', 'type' => 'Document', 'taille' => '1.1 Mo', 'telechargements' => 98, 'tint' => '#F9E9E9', 'emoji' => '📄'],
        ];
    }

    public function render()
    {
        $ressources = array_map(function ($r, $i) {
            $visible = $this->visibles[$i];
            $r['index'] = $i;
            $r['visible'] = $visible;

            return $r;
        }, $this->data(), array_keys($this->data()));

        return view('livewire.admin.ressources', ['ressources' => $ressources]);
    }
}
