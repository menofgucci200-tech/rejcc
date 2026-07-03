<?php

namespace App\Livewire\Admin;

use App\Models\Member;
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

        Member::findOrFail($id)->update(['statut' => $statut]);
    }

    public function render()
    {
        return view('livewire.admin.adhesions', [
            'adhesions' => Member::orderByDesc('created_at')->get(),
        ]);
    }
}
