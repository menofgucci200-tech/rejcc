<?php

namespace App\Livewire;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Page publique d'inscription à un événement, cible des QR codes. Toute
 * personne (membre ou non) peut s'inscrire tant que les inscriptions sont
 * ouvertes et qu'il reste des places.
 */
#[Layout('layouts.site')]
#[Title('Inscription à l\'événement')]
class EventSignup extends Component
{
    public string $slug = '';

    /** Données de l'événement (titre, date, lieu, état des inscriptions…). */
    public array $event = [];

    public string $prenom = '';

    public string $nom = '';

    public string $telephone = '';

    public string $email = '';

    public bool $is_member = false;

    public bool $submitted = false;

    public function mount(string $slug): void
    {
        $this->slug = $slug;

        $result = Api::get('/event-signup/'.rawurlencode($slug));
        if (! ($result['ok'] ?? false)) {
            abort(404);
        }

        $this->event = $result['event'];
    }

    public function register(): void
    {
        $this->validate([
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'telephone' => 'required|string|min:8|max:30',
            'email' => 'nullable|email|max:150',
        ], [
            'prenom.required' => 'Indiquez votre prénom.',
            'nom.required' => 'Indiquez votre nom.',
            'telephone.required' => 'Indiquez votre numéro de téléphone / WhatsApp.',
            'telephone.min' => 'Le numéro de téléphone est trop court.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
        ]);

        $result = Api::post('/event-signup/'.rawurlencode($this->slug), [
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'email' => $this->email ?: null,
            'is_member' => $this->is_member,
        ]);

        if (! ($result['ok'] ?? false)) {
            // Événement complet / fermé / doublon : on rafraîchit l'état et on affiche le message.
            if (isset($result['event'])) {
                $this->event = $result['event'];
            }
            $this->addError('telephone', $result['message'] ?? 'L\'inscription a échoué. Réessayez.');

            return;
        }

        $this->event = $result['event'] ?? $this->event;
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.event-signup');
    }
}
