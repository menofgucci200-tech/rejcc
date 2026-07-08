<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member')]
class Documents extends Component
{
    public function render()
    {
        $docs = Collection::make(Api::get('/documents', [], Api::token())['documents'] ?? [])
            ->map(fn ($d) => (object) $d);

        return view('livewire.member.documents', [
            'docs' => $docs,
            'categories' => $docs->pluck('category')->unique(),
        ]);
    }
}
