<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\Content\MembershipContent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member')]
class ProfileEditor extends Component
{
    public string $prenom = '';

    public string $nom = '';

    public string $telephone = '';

    public string $genre = '';

    public string $ville = '';

    public string $secteur = '';

    public string $profil = '';

    public string $organisation = '';

    public string $bio = '';

    public string $status = 'idle';

    public function mount(): void
    {
        $user = Api::user();
        $this->prenom = $user->prenom ?? '';
        $this->nom = $user->nom ?? '';
        $this->telephone = $user->telephone ?? '';
        $this->genre = $user->genre ?? '';
        $this->ville = $user->ville ?? '';
        $this->secteur = $user->secteur ?? '';
        $this->profil = $user->profil ?? '';
        $this->organisation = $user->organisation ?? '';
        $this->bio = $user->bio ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'prenom' => 'sometimes|string|min:2|max:80',
            'nom' => 'sometimes|string|min:2|max:80',
            'telephone' => ['sometimes', 'regex:/^[0-9]{10}$/'],
            'genre' => 'nullable|in:Homme,Femme',
            'ville' => 'nullable|string|max:80',
            'secteur' => 'nullable|string|max:100',
            'profil' => 'nullable|in:etudiant,porteur,entrepreneur',
            'organisation' => 'nullable|string|max:120',
            'bio' => 'nullable|string|max:600',
        ]);

        $result = Api::put('/auth/profile', $validated, Api::token());

        if ($result['ok'] ?? false) {
            session(['api_user' => $result['user']]);
            $this->status = 'saved';
        }
    }

    public function render()
    {
        return view('livewire.member.profile-editor', [
            'profiles' => MembershipContent::profiles(),
        ]);
    }
}
