<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member')]
class Directory extends Component
{
    public string $query = '';

    public function render()
    {
        $token = Api::token();
        $me = Api::user();
        $q = trim(mb_strtolower($this->query));

        $members = Collection::make(Api::get('/members', [], $token)['members'] ?? [])
            ->reject(fn ($m) => $m['id'] === $me->id)
            ->when($q !== '', function ($collection) use ($q) {
                return $collection->filter(function ($m) use ($q) {
                    $fullName = mb_strtolower(trim(($m['prenom'] ?? '').' '.($m['nom'] ?? '')));

                    return str_contains($fullName, $q)
                        || str_contains(mb_strtolower($m['secteur'] ?? ''), $q)
                        || str_contains(mb_strtolower($m['ville'] ?? ''), $q);
                });
            })
            ->sortBy('prenom')
            ->map(fn ($m) => (object) $m)
            ->values();

        return view('livewire.member.directory', ['members' => $members]);
    }
}
