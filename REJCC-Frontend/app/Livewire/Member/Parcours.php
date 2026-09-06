<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Parcours guidés : séquences de formations vers un objectif, avec
 * déblocage progressif et badge à la clé.
 */
#[Layout('layouts.member-light')]
class Parcours extends Component
{
    public function render()
    {
        $paths = Collection::make(Api::get('/paths', [], Api::token())['paths'] ?? []);

        return view('livewire.member.parcours', ['paths' => $paths]);
    }
}
