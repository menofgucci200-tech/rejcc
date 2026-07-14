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

    /** Fichier en cours d'upload. Nommé différemment de la variable de vue
     *  « photo » (URL enregistrée) : les propriétés publiques Livewire
     *  écrasent les données passées à la vue en cas de collision de nom. */
    public $photoUpload;

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

    public function updatedPhotoUpload(): void
    {
        $this->validate([
            'photoUpload' => 'image|max:2048', // 2 Mo
        ], [
            'photoUpload.image' => 'Choisissez une image (JPG, PNG, WebP…).',
            'photoUpload.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ], ['photoUpload' => 'photo']);

        // Stockée sur le disque public du frontend (rejcc.site/storage/...),
        // puis l'URL est enregistrée côté backend via l'API.
        $path = $this->photoUpload->store('members', 'public');
        $url = url('storage/'.$path);

        $result = Api::put('/auth/profile', ['photo' => $url], Api::token());

        if ($result['ok'] ?? false) {
            session(['api_user' => $result['user']]);
            $this->message = 'Photo mise à jour.';
        } else {
            $this->addError('photoUpload', $result['message'] ?? "L'enregistrement de la photo a échoué.");
        }

        $this->reset('photoUpload');
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
