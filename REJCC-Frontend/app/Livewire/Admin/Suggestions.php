<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Retours des comptes de test (bugs, suggestions), par département. */
#[Layout('layouts.admin-light')]
class Suggestions extends Component
{
    #[Url]
    public string $departement = '';

    #[Url]
    public string $statut = '';

    public ?int $ouverte = null;

    public string $reponse = '';

    public function ouvrir(int $id, ?string $reponse): void
    {
        $this->ouverte = $this->ouverte === $id ? null : $id;
        $this->reponse = (string) $reponse;
    }

    public function changerStatut(int $id, string $statut): void
    {
        Api::put("/admin/suggestions/{$id}", ['statut' => $statut], Api::token());
        AdminNav::oublier();
    }

    public function enregistrerReponse(int $id): void
    {
        Api::put("/admin/suggestions/{$id}", ['reponse' => trim($this->reponse) ?: null], Api::token());
        $this->ouverte = null;
    }

    public function render()
    {
        $r = Api::get('/admin/suggestions', array_filter(['departement' => $this->departement, 'statut' => $this->statut]), Api::token());

        return view('livewire.admin.suggestions', [
            'suggestions' => collect($r['suggestions'] ?? [])->map(function ($s) {
                $s['created_at'] = Carbon::parse($s['created_at']);

                return (object) $s;
            }),
            'departements' => collect($r['departements'] ?? []),
            'statuts' => $r['statuts'] ?? [],
        ]);
    }
}
