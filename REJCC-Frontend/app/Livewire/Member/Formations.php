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

    public ?string $message = null;

    public function seDesinscrire(int $id): void
    {
        $result = Api::delete("/formations/{$id}/enroll", Api::token());
        $this->message = ($result['ok'] ?? false) ? 'Vous êtes désinscrit de la formation.' : ($result['message'] ?? 'Désinscription impossible.');
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
                    'has_modules' => (bool) ($f['has_modules'] ?? false),
                    'certifiante' => (bool) ($f['is_certifying'] ?? false),
                    'infos' => collect([
                        $f['duration'] ?? null,
                        $f['level'] ?? null,
                        ($f['modules_reels'] ?? 0) ? $f['modules_reels'].' module'.($f['modules_reels'] > 1 ? 's' : '') : null,
                    ])->filter()->join(' · '),
                    'derniere_activite' => $f['derniere_activite'] ?? null,
                    'examen' => (bool) ($f['examen_a_passer'] ?? false),
                    'detail' => ($f['examen_a_passer'] ?? false)
                        ? 'Modules terminés — examen final à passer pour obtenir le certificat'
                        : ($termine
                        ? 'Terminée le '.Carbon::parse($f['completed_at'] ?? now())->translatedFormat('j F Y')
                        : ($f['module_courant'] ?? null
                            ? "Module en cours : {$f['module_courant']}"
                            : "Module {$moduleCourant} sur {$modules}".($f['duration'] ? " · {$f['duration']}" : ''))),
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
            // Formation à reprendre : la plus récemment suivie parmi celles en cours.
            // (une formation dont le contenu est encore en préparation passe après).
            'enCours' => $cours->where('etat', 'encours')
                ->sortByDesc(fn ($c) => ($c['has_modules'] ? '1' : '0').($c['derniere_activite'] ?? ''))
                ->first(),
            'aucune' => $cours->isEmpty(),
        ]);
    }
}
