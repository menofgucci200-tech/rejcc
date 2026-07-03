<?php

namespace App\Livewire\Admin;

use App\Models\Contact;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Contacts extends Component
{
    public ?int $expanded = null;

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function markTraite(int $id): void
    {
        Contact::findOrFail($id)->update(['traite' => true]);
    }

    public function render()
    {
        $contacts = Contact::orderByDesc('created_at')->get();

        return view('livewire.admin.contacts', [
            'pending' => $contacts->where('traite', false)->values(),
            'done' => $contacts->where('traite', true)->values(),
        ]);
    }
}
