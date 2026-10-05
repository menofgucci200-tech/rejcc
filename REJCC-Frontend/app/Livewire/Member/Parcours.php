<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Parcours guidés : séquences de formations vers un objectif, dans un
 * ordre conseillé, avec un badge à la clé.
 */
#[Layout('layouts.member-light')]
class Parcours extends Component
{
    public function render()
    {
        $paths = Collection::make(Api::get('/paths', [], Api::token())['paths'] ?? []);

        // En cours d'abord (là où le membre a déjà avancé), puis à découvrir, puis réussis.
        $groupes = collect([
            'En cours' => $paths->filter(fn ($p) => ! $p['badge_obtenu'] && ($p['commence'] ?? false)),
            'À découvrir' => $paths->filter(fn ($p) => ! $p['badge_obtenu'] && ! ($p['commence'] ?? false)),
            'Terminés' => $paths->filter(fn ($p) => $p['badge_obtenu']),
        ])->filter(fn ($liste) => $liste->isNotEmpty())->map->values();

        return view('livewire.member.parcours', ['paths' => $paths, 'groupes' => $groupes]);
    }
}
