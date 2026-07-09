<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Partenariats extends Component
{
    public function accepter(int $id): void
    {
        Api::put("/admin/partenariats/{$id}", ['statut' => 'accepte'], Api::token());
    }

    public function refuser(int $id): void
    {
        Api::put("/admin/partenariats/{$id}", ['statut' => 'refuse'], Api::token());
    }

    public function render()
    {
        $demandes = Collection::make(Api::get('/admin/partenariats', [], Api::token())['requests'] ?? [])
            ->map(function ($d) {
                $d['created_at'] = Carbon::parse($d['created_at']);
                $d['initiales'] = collect(explode(' ', $d['organisation']))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');

                return (object) $d;
            });

        return view('livewire.admin.partenariats', ['demandes' => $demandes]);
    }
}
