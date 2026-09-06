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

    public ?string $message = null;

    public function mount(int $pathId): void
    {
        $this->pathId = $pathId;
    }

    public function demarrer(int $formationId): void
    {
        $result = Api::post("/formations/{$formationId}/enroll", [], Api::token());
        $this->message = ($result['ok'] ?? false)
            ? 'Formation ajoutée à « Mes formations » !'
            : ($result['message'] ?? 'Une erreur est survenue.');
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
