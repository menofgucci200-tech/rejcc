<?php

namespace App\Livewire\Member;

use App\Support\Content\MembershipContent;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.member')]
class ProfileEditor extends Component
{
    use WithFileUploads;

    public string $prenom = '';

    public string $nom = '';

    public string $telephone = '';

    public string $genre = '';

    public string $ville = '';

    public string $secteur = '';

    public string $profil = '';

    public string $organisation = '';

    public string $bio = '';

    public $photo;

    public string $status = 'idle';

    public function mount(): void
    {
        $user = auth()->user();
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
            'photo' => 'nullable|image|max:2048',
        ]);

        $user = auth()->user();
        unset($validated['photo']);
        $user->fill($validated);

        if ($user->isDirty(['prenom', 'nom'])) {
            $user->name = $user->prenom.' '.$user->nom;
        }

        if ($this->photo) {
            $path = $this->photo->store('avatars', 'public');
            $user->photo = $path;
        }

        $user->save();

        $this->status = 'saved';
        $this->photo = null;
    }

    public function render()
    {
        return view('livewire.member.profile-editor', [
            'profiles' => MembershipContent::profiles(),
        ]);
    }
}
