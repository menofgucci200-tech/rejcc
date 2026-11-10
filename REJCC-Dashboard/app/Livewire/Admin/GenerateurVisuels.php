<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Générateur de visuels de la Communication : gabarits typographiques sobres
 * dans la charte (annonce d'événement, rappel, citation, bienvenue, portrait
 * CathoImpact, annonce générique), aux formats post, carré et story. Tout se
 * passe dans le navigateur (canvas, polices du site, logo local) : rien
 * n'est envoyé au serveur, la photo éventuelle reste sur l'ordinateur.
 */
#[Layout('layouts.admin-light')]
class GenerateurVisuels extends Component
{
    public function render()
    {
        return view('livewire.admin.generateur-visuels');
    }
}
