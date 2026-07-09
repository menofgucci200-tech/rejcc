<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Newsletter extends Component
{
    public function render()
    {
        $abonnes = Collection::make(Api::get('/admin/newsletter', [], Api::token())['subscribers'] ?? [])
            ->map(function ($s) {
                $s['created_at'] = Carbon::parse($s['created_at']);

                return (object) $s;
            });

        $nouveauxCeMois = $abonnes->filter(fn ($s) => $s->created_at->isCurrentMonth())->count();

        return view('livewire.admin.newsletter', [
            'abonnes' => $abonnes,
            'total' => $abonnes->count(),
            'nouveauxCeMois' => $nouveauxCeMois,
        ]);
    }
}
