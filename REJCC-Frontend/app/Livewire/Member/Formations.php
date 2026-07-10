<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\CategoryPalette;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Formations extends Component
{
    public string $filtre = 'tous';

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    public function validerModule(int $id): void
    {
        Api::post("/formations/{$id}/complete-module", [], Api::token());
    }

    protected function cours(): Collection
    {
        return Collection::make(Api::get('/my-formations', [], Api::token())['formations'] ?? [])
            ->map(function (array $f) {
                $palette = CategoryPalette::for($f['category']);
                $termine = (bool) $f['completed'];
                $pct = $termine ? 100 : (int) $f['progress'];
                $modules = max(1, (int) $f['modules_count']);
                $moduleCourant = min($modules, max(1, (int) ceil($pct / 100 * $modules)));

                return [
                    'id' => $f['id'],
                    'titre' => $f['title'],
                    'categorie' => $f['category'],
                    'pct' => $pct,
                    'etat' => $termine ? 'termine' : 'encours',
                    'from' => $palette['from'],
                    'to' => $palette['to'],
                    'detail' => $termine
                        ? 'Terminée le '.Carbon::parse($f['completed_at'] ?? now())->translatedFormat('j F Y')
                        : "Module {$moduleCourant} sur {$modules}".($f['duration'] ? " · {$f['duration']}" : ''),
                ];
            });
    }

    public function render()
    {
        $cours = $this->cours();

        return view('livewire.member.formations', [
            'cours' => $cours
                ->when($this->filtre !== 'tous', fn ($c) => $c->where('etat', $this->filtre))
                ->values(),
            'enCours' => $cours->firstWhere('etat', 'encours'),
        ]);
    }
}
