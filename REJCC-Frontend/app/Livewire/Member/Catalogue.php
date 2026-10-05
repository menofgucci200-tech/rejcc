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

    #[Url(as: 'categorie', except: '')]
    public string $categorie = '';

    #[Url(except: 'recentes')]
    public string $tri = 'recentes';

    public function effacerRecherche(): void
    {
        $this->q = '';
    }

    public function reinitialiser(): void
    {
        $this->reset(['q', 'categorie', 'filtre']);
        $this->tri = 'recentes';
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

        // Inscription faite : on commence directement la formation (si son contenu est en ligne).
        if (! $this->erreur) {
            $formation = collect(Api::get('/formations', [], Api::token())['formations'] ?? [])->firstWhere('id', $id);
            if ($formation['has_modules'] ?? false) {
                $this->redirectRoute('espace-membre.formations.detail', $id, navigate: true);
            }
        }
    }

    public function render()
    {
        $toutes = Collection::make(Api::get('/formations', [], Api::token())['formations'] ?? []);
        $categories = $toutes->pluck('category')->filter()->unique()->sort()->values()->all();

        $cours = $toutes
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
                    'certifiante' => (bool) ($f['certifiante'] ?? false),
                    'inscrit' => (bool) $f['enrolled'],
                    'has_modules' => (bool) ($f['has_modules'] ?? false),
                    'termine' => (bool) ($f['completed'] ?? false),
                    'description' => $f['description'] ?? '',
                    'inscrits' => (int) ($f['inscrits'] ?? 0),
                    'publiee_le' => $f['publiee_le'] ?? '',
                    'image' => $f['image_url'] ?? null,
                ];
            })
            ->when($this->filtre === 'gratuit', fn ($c) => $c->where('gratuit', true))
            ->when($this->filtre === 'certifiante', fn ($c) => $c->where('certifiante', true))
            ->when(trim($this->q) !== '', function ($c) {
                $q = Str::lower(Str::ascii(trim($this->q)));

                return $c->filter(fn ($f) => str_contains(Str::lower(Str::ascii($f['titre'].' '.$f['tag'].' '.$f['description'])), $q));
            })
            ->when($this->categorie !== '', fn ($c) => $c->where('tag', $this->categorie))
            ->pipe(fn ($c) => match ($this->tri) {
                'populaires' => $c->sortByDesc('inscrits'),
                'az' => $c->sortBy(fn ($f) => Str::lower(Str::ascii($f['titre']))),
                default => $c->sortByDesc('publiee_le'),
            })
            ->values();

        return view('livewire.member.catalogue', [
            'cours' => $cours,
            'categories' => $categories,
            'aucuneFormation' => $toutes->isEmpty(),
            'accesLibre' => ! (Api::user()->subscriptions_enforced ?? true),
            'filtresActifs' => trim($this->q) !== '' || $this->categorie !== '' || $this->filtre !== 'toutes',
        ]);
    }
}
