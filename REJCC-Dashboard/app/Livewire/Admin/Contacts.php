<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Contacts extends Component
{
    public ?int $expanded = null;

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function markTraite(int $id): void
    {
        Api::post("/admin/contacts/{$id}/traite", [], Api::token());
    }

    public function render()
    {
        $contacts = Collection::make(Api::get('/admin/contacts', [], Api::token())['contacts'] ?? [])
            ->map(function ($c) {
                $c['created_at'] = Carbon::parse($c['created_at']);

                return (object) $c;
            });

        return view('livewire.admin.contacts', [
            'pending' => $contacts->where('traite', false)->values(),
            'done' => $contacts->where('traite', true)->values(),
        ]);
    }
}
