<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Adhesions extends Component
{
    public ?int $expanded = null;

    public string $recherche = '';

    public string $filtreStatut = 'tous'; // tous | en_attente | accepte | refuse

    public int $page = 1;

    public function updatedRecherche(): void
    {
        $this->page = 1;
    }

    public function setFiltreStatut(string $statut): void
    {
        $this->filtreStatut = $statut;
        $this->page = 1;
    }

    public function gotoPage(int $p): void
    {
        $this->page = max(1, $p);
    }

    public function toggleDetail(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public ?int $rejectingId = null;

    public string $motif = '';

    public function accept(int $id): void
    {
        Api::post("/admin/membership-applications/{$id}/accept", [], Api::token());
    }

    public function openReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->motif = '';
        $this->resetValidation();
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->motif = '';
    }

    public function confirmReject(): void
    {
        $this->validate(['motif' => 'nullable|string|max:500']);

        if ($this->rejectingId) {
            Api::post("/admin/membership-applications/{$this->rejectingId}/reject", array_filter([
                'motif' => trim($this->motif) ?: null,
            ]), Api::token());
        }

        $this->cancelReject();
    }

    public function render()
    {
        $params = ['page' => $this->page];
        if (trim($this->recherche) !== '') {
            $params['q'] = trim($this->recherche);
        }
        if ($this->filtreStatut !== 'tous') {
            $params['statut'] = $this->filtreStatut;
        }

        $result = Api::get('/admin/membership-applications', $params, Api::token());

        $candidatures = Collection::make($result['applications'] ?? [])
            ->map(function ($a) {
                $a['created_at'] = Carbon::parse($a['created_at']);

                return (object) $a;
            });

        return view('livewire.admin.adhesions', [
            'candidatures' => $candidatures,
            'meta' => $result['meta'] ?? [],
        ]);
    }
}
