<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** « Mes sessions » d'un membre formateur : les sessions live qu'il anime. */
#[Layout('layouts.member-light')]
class SessionsAnimees extends Component
{
    public function render()
    {
        $r = Api::get('/mes-sessions-animees', [], Api::token());

        return view('livewire.member.sessions-animees', ['sessions' => $r['sessions'] ?? [], 'formateur' => (bool) ($r['formateur'] ?? false)]);
    }
}
