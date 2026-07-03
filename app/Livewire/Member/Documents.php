<?php

namespace App\Livewire\Member;

use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member')]
class Documents extends Component
{
    public function render()
    {
        $docs = Document::orderBy('category')->orderBy('title')->get();

        return view('livewire.member.documents', [
            'docs' => $docs,
            'categories' => $docs->pluck('category')->unique(),
        ]);
    }
}
