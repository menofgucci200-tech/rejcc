<?php

namespace App\Livewire\Member;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member')]
class Directory extends Component
{
    public string $query = '';

    public function render()
    {
        $q = trim(mb_strtolower($this->query));

        $members = User::where('role', 'member')
            ->where('id', '!=', auth()->id())
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->whereRaw('LOWER(CONCAT(prenom, " ", nom)) LIKE ?', ["%{$q}%"])
                        ->orWhereRaw('LOWER(secteur) LIKE ?', ["%{$q}%"])
                        ->orWhereRaw('LOWER(ville) LIKE ?', ["%{$q}%"]);
                });
            })
            ->orderBy('prenom')
            ->get(['id', 'prenom', 'nom', 'ville', 'secteur', 'profil', 'organisation', 'photo']);

        return view('livewire.member.directory', ['members' => $members]);
    }
}
