<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Audit extends Component
{
    public function render()
    {
        return view('livewire.admin.audit');
    }
}
