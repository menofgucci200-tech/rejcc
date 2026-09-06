<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Détail d'une formation à contenu réel : sommaire des modules (titre,
 * description, vidéo/document), consultation puis validation dans l'ordre.
 */
#[Layout('layouts.member-light')]
class FormationDetail extends Component
{
    public int $formationId;

    public ?int $moduleOuvert = null;

    public ?string $message = null;

    public function mount(int $formationId): void
    {
        $this->formationId = $formationId;
    }

    public function toggleModule(int $moduleId): void
    {
        $this->moduleOuvert = $this->moduleOuvert === $moduleId ? null : $moduleId;
    }

    public function validerModule(int $moduleId): void
    {
        $result = Api::post("/formations/{$this->formationId}/modules/{$moduleId}/complete", [], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->message = $result['message'] ?? 'Une erreur est survenue.';

            return;
        }

        $this->message = ($result['completed'] ?? false) ? 'Formation terminée, bravo !' : 'Module validé !';
        $this->moduleOuvert = null;
    }

    public function render()
    {
        $result = Api::get("/formations/{$this->formationId}/modules", [], Api::token());

        return view('livewire.member.formation-detail', [
            'ok' => $result['ok'] ?? false,
            'formation' => $result['formation'] ?? null,
            'modules' => $result['modules'] ?? [],
            'progress' => $result['progress'] ?? 0,
            'completed' => $result['completed'] ?? false,
        ]);
    }
}
