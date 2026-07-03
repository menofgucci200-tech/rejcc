<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Members extends Component
{
    public string $query = '';

    public function toggleRole(int $id): void
    {
        $user = User::findOrFail($id);
        $user->role = $user->role === 'admin' ? 'member' : 'admin';
        $user->save();
    }

    public function deleteMember(int $id): void
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return;
        }
        $user->delete();
    }

    public function render()
    {
        $q = trim($this->query);

        $members = User::orderByDesc('created_at')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('prenom', 'like', "%{$q}%")
                        ->orWhere('nom', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->get(['id', 'prenom', 'nom', 'email', 'telephone', 'ville', 'profil', 'secteur', 'role', 'created_at']);

        return view('livewire.admin.members', ['members' => $members]);
    }
}
