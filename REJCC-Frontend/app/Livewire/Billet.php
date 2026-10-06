<?php

namespace App\Livewire;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Billet d'un invité inscrit par le formulaire public : QR code à présenter
 * à l'entrée (pointage), rappel de la date et du lieu.
 */
#[Layout('layouts.site')]
#[Title('Mon billet')]
class Billet extends Component
{
    public array $billet = [];

    public function mount(string $code): void
    {
        $result = Api::get('/billet/'.rawurlencode($code));
        if (! ($result['ok'] ?? false)) {
            abort(404);
        }
        $this->billet = $result['billet'];
    }

    public function render()
    {
        return view('livewire.billet');
    }
}
