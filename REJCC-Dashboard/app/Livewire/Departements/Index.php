<?php

namespace App\Livewire\Departements;

use App\Support\Api;
use App\Support\Espace;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Les 11 départements du Bureau, pour l'administrateur général : missions,
 * rubriques ouvertes et comptes de chaque département.
 */
#[Layout('layouts.admin-light')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(Espace::adminGeneral(session('api_user')), 403, 'Réservé à l\'administrateur général.');
    }

    public function render()
    {
        return view('livewire.departements.index', [
            'departements' => Api::get('/admin/departements', [], Api::token())['departements'] ?? [],
        ]);
    }
}
