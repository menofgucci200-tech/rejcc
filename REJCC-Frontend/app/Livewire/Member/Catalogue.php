<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\CategoryPalette;
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

    public function inscrire(int $id): void
    {
        Api::post("/formations/{$id}/enroll", [], Api::token());
    }

    public function render()
    {
        $cours = Collection::make(Api::get('/formations', [], Api::token())['formations'] ?? [])
            ->map(function (array $f) {
                $palette = CategoryPalette::for($f['category']);

                return [
                    'id' => $f['id'],
                    'titre' => $f['title'],
                    'tag' => $f['category'],
                    'tagColor' => $palette['tag'],
                    'duree' => $f['duration'] ?? '—',
                    'niveau' => $f['level'] ?? 'Tous niveaux',
                    'from' => $palette['from'],
                    'to' => $palette['to'],
                    'gratuit' => (bool) $f['is_free'],
                    'certifiante' => (bool) $f['is_certifying'],
                    'inscrit' => (bool) $f['enrolled'],
                ];
            })
            ->when($this->filtre === 'gratuit', fn ($c) => $c->where('gratuit', true))
            ->when($this->filtre === 'certifiante', fn ($c) => $c->where('certifiante', true))
            ->values();

        return view('livewire.member.catalogue', ['cours' => $cours]);
    }
}
