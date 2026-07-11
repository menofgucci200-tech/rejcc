<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Ressources extends Component
{
    /** Habillage visuel par type de ressource. */
    public const TYPES = [
        'Ebook' => ['icon' => 'book-open', 'color' => '#4F6FBF'],
        'Modèle' => ['icon' => 'file-text', 'color' => '#AC0100'],
        'Vidéo' => ['icon' => 'video', 'color' => '#22A85A'],
        'Audio' => ['icon' => 'headphones', 'color' => '#F5A623'],
        'Document' => ['icon' => 'folder-open', 'color' => '#4F6FBF'],
    ];

    public string $filtre = 'tous';

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    /** Comptabilise le téléchargement (le lien s'ouvre côté navigateur). */
    public function compter(int $id): void
    {
        Api::post("/resources/{$id}/download", [], Api::token());
    }

    public function render()
    {
        $toutes = Collection::make(Api::get('/resources', [], Api::token())['resources'] ?? [])
            ->map(function (array $r) {
                $style = self::TYPES[$r['type']] ?? self::TYPES['Document'];

                return [
                    'id' => $r['id'],
                    'titre' => $r['title'],
                    'type' => $r['type'],
                    'description' => $r['description'],
                    'url' => $r['url'],
                    'taille' => $r['size'],
                    'telechargements' => (int) $r['downloads'],
                    'icon' => $style['icon'],
                    'color' => $style['color'],
                ];
            });

        $categories = $toutes->groupBy('type')->map->count();

        $ressources = $toutes
            ->when($this->filtre !== 'tous', fn ($c) => $c->where('type', $this->filtre))
            ->values();

        return view('livewire.member.ressources', [
            'ressources' => $ressources,
            'categories' => $categories,
        ]);
    }
}
