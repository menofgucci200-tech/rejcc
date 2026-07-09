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

    public function toggleDetail(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function accept(int $id): void
    {
        Api::post("/admin/membership-applications/{$id}/accept", [], Api::token());
    }

    public function reject(int $id): void
    {
        Api::post("/admin/membership-applications/{$id}/reject", [], Api::token());
    }

    public function updateStatut(int $id, string $statut): void
    {
        if (! in_array($statut, ['en_attente', 'valide', 'rejete'], true)) {
            return;
        }

        Api::put("/admin/adhesions/{$id}", ['statut' => $statut], Api::token());
    }

    public function render()
    {
        $token = Api::token();

        $candidatures = Collection::make(Api::get('/admin/membership-applications', [], $token)['applications'] ?? [])
            ->map(function ($a) {
                $a['created_at'] = Carbon::parse($a['created_at']);

                return (object) $a;
            });

        $demandes = Collection::make(Api::get('/admin/adhesions', [], $token)['adhesions'] ?? [])
            ->map(function ($a) {
                $a['created_at'] = Carbon::parse($a['created_at']);

                return (object) $a;
            });

        return view('livewire.admin.adhesions', [
            'candidatures' => $candidatures,
            'demandes' => $demandes,
        ]);
    }
}
