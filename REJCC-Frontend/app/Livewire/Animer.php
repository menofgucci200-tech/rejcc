<?php

namespace App\Livewire;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Espace animateur d'une session live, ouvert par le lien personnel du
 * formateur (sans compte) : horaires, inscrits et accès à la visio à partir
 * d'une heure avant le début.
 */
#[Layout('layouts.site', ['noindex' => true])]
#[Title('Espace animateur')]
class Animer extends Component
{
    #[Locked]
    public string $jeton = '';

    public function mount(string $jeton): void
    {
        $this->jeton = $jeton;
    }

    public function render()
    {
        $r = Api::get('/animer/'.$this->jeton);

        return view('livewire.animer', ['session' => ($r['ok'] ?? false) ? $r['session'] : null, 'erreur' => $r['message'] ?? null]);
    }
}
