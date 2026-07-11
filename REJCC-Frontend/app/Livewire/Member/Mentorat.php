<?php

namespace App\Livewire\Member;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Mentorat extends Component
{
    public function render()
    {
        return view('livewire.member.mentorat');
    }
}
