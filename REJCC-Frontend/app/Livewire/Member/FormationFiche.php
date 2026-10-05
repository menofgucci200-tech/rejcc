<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\CategoryPalette;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Fiche de présentation d'une formation du catalogue : description,
 * programme, évaluation (quiz, examen), support consultable sur la
 * plateforme, et inscription.
 */
#[Layout('layouts.member-light')]
class FormationFiche extends Component
{
    public int $formationId;

    public ?string $erreur = null;

    public function mount(int $formationId): void
    {
        $this->formationId = $formationId;
    }

    public function inscrire(): void
    {
        $result = Api::post("/formations/{$this->formationId}/enroll", [], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->erreur = $result['message'] ?? 'Inscription impossible.';

            return;
        }

        // Inscription faite : on commence directement la formation.
        $this->redirectRoute('espace-membre.formations.detail', $this->formationId, navigate: true);
    }

    public function render()
    {
        $result = Api::get("/formations/{$this->formationId}/fiche", [], Api::token());
        $f = $result['formation'] ?? null;

        return view('livewire.member.formation-fiche', [
            'f' => $f,
            'palette' => $f ? CategoryPalette::for($f['category']) : null,
            'accesLibre' => ! (\App\Support\Api::user()->subscriptions_enforced ?? true),
        ]);
    }
}
