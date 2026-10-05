<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\CategoryPalette;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Catalogue extends Component
{
    public string $filtre = 'toutes';

    /** Recherche reçue de la barre du haut (?q=). */
    #[Url(except: '')]
    public string $q = '';

    public function effacerRecherche(): void
    {
        $this->q = '';
    }

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    public ?string $erreur = null;

    public function inscrire(int $id): void
    {
        $result = Api::post("/formations/{$id}/enroll", [], Api::token());
        $this->erreur = ($result['ok'] ?? false) ? null : ($result['message'] ?? 'Inscription impossible.');
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
                    'has_modules' => (bool) ($f['has_modules'] ?? false),
                    'media' => $f['media_url'] ?? null,
                ];
            })
            ->when($this->filtre === 'gratuit', fn ($c) => $c->where('gratuit', true))
            ->when($this->filtre === 'certifiante', fn ($c) => $c->where('certifiante', true))
            ->when(trim($this->q) !== '', function ($c) {
                $q = Str::lower(Str::ascii(trim($this->q)));

                return $c->filter(fn ($f) => str_contains(Str::lower(Str::ascii($f['titre'].' '.$f['tag'])), $q));
            })
            ->values();

        return view('livewire.member.catalogue', ['cours' => $cours]);
    }
}
