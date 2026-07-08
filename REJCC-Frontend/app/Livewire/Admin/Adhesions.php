<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Adhesions extends Component
{
    public function updateStatut(int $id, string $statut): void
    {
        if (! in_array($statut, ['en_attente', 'valide', 'rejete'], true)) {
            return;
        }

        Api::put("/admin/adhesions/{$id}", ['statut' => $statut], Api::token());
    }

    public function render()
    {
        $adhesions = Collection::make(Api::get('/admin/adhesions', [], Api::token())['adhesions'] ?? [])
            ->map(function ($a) {
                $a['created_at'] = Carbon::parse($a['created_at']);

                return (object) $a;
            });

        return view('livewire.admin.adhesions', ['adhesions' => $adhesions]);
    }
}
