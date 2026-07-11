<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.member-light')]
class Carte extends Component
{
    use WithFileUploads;

    public $photo;

    public ?string $message = null;

    public function mount(): void
    {
        // Sessions ouvertes avant l'ajout du numéro/code : on rafraîchit le profil.
        $user = Api::user();
        if (! ($user->code ?? null)) {
            $result = Api::get('/auth/me', [], Api::token());
            if ($result['ok'] ?? false) {
                session(['api_user' => $result['user']]);
            }
        }
    }

    public function updatedPhoto(): void
    {
        $this->validate([
            'photo' => 'image|max:2048', // 2 Mo
        ]);

        // Stockée sur le disque public du frontend (rejcc.site/storage/...),
        // puis l'URL est enregistrée côté backend via l'API.
        $path = $this->photo->store('members', 'public');
        $url = url('storage/'.$path);

        $result = Api::put('/auth/profile', ['photo' => $url], Api::token());

        if ($result['ok'] ?? false) {
            session(['api_user' => $result['user']]);
            $this->message = 'Photo mise à jour.';
        } else {
            $this->addError('photo', $result['message'] ?? "L'enregistrement de la photo a échoué.");
        }

        $this->reset('photo');
    }

    public function render()
    {
        $user = Api::user();

        return view('livewire.member.carte', [
            'name' => trim(($user->prenom ?? '').' '.($user->nom ?? '')) ?: ($user->email ?? 'Membre'),
            'roleLabel' => $user->role_label ?? 'Membre officiel',
            'role' => $user->role ?? 'member',
            'numero' => $user->numero ?? $user->reference ?? '—',
            'code' => $user->code ?? '',
            'photo' => $user->photo ?? null,
            'dateAdhesion' => ($user->date_adhesion ?? null)
                ? \Carbon\Carbon::parse($user->date_adhesion)->translatedFormat('d F Y')
                : null,
        ]);
    }
}
