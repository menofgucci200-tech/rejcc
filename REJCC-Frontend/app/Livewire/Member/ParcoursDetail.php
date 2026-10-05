<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class ParcoursDetail extends Component
{
    public int $pathId;

    public ?string $erreur = null;

    public bool $abonnementRequis = false;

    public function mount(int $pathId): void
    {
        $this->pathId = $pathId;
    }

    public function demarrer(int $formationId): void
    {
        $this->erreur = null;
        $this->abonnementRequis = false;
        $result = Api::post("/formations/{$formationId}/enroll", [], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->erreur = $result['message'] ?? 'Inscription impossible, réessayez.';
            $this->abonnementRequis = ($result['code'] ?? null) === 'subscription_required';

            return;
        }

        // Inscription faite : on ouvre directement la formation.
        $this->redirectRoute('espace-membre.formations.detail', $formationId, navigate: true);
    }

    public function render()
    {
        $result = Api::get("/paths/{$this->pathId}", [], Api::token());

        return view('livewire.member.parcours-detail', [
            'ok' => $result['ok'] ?? false,
            'path' => $result['path'] ?? null,
            'formations' => Collection::make($result['formations'] ?? []),
        ]);
    }
}
