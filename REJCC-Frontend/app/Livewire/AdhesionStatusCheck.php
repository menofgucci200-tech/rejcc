<?php

namespace App\Livewire;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.site')]
class AdhesionStatusCheck extends Component
{
    public string $email = '';

    public bool $checked = false;

    public ?string $statut = null;

    public ?string $nomPrenoms = null;

    public function check(): void
    {
        $this->validate(['email' => 'required|email']);

        $result = Api::post('/membership-applications/status', ['email' => $this->email]);

        $this->checked = true;
        $this->statut = ($result['ok'] ?? false) ? $result['statut'] : null;
        $this->nomPrenoms = $result['nom_prenoms'] ?? null;

        if (! ($result['ok'] ?? false)) {
            $this->addError('email', $result['message'] ?? 'Aucune candidature trouvée pour cette adresse e-mail.');
        }
    }

    public function render()
    {
        return view('livewire.adhesion-status-check');
    }
}
