<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\CategoryPalette;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Certificats extends Component
{
    public function render()
    {
        $certificats = Collection::make(Api::get('/my-certificates', [], Api::token())['certificates'] ?? [])
            ->map(function (array $c) {
                $palette = CategoryPalette::for($c['category'] ?? $c['title']);

                return [
                    'titre' => $c['title'],
                    'reference' => $c['reference'],
                    'date' => Carbon::parse($c['issued_at'])->translatedFormat('j F Y'),
                    'from' => $palette['from'],
                    'to' => $palette['to'],
                ];
            })
            ->all();

        return view('livewire.member.certificats', ['certificats' => $certificats]);
    }
}
