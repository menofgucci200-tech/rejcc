<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Groupes sectoriels : pôles qui regroupent les membres par domaine
 * d'activité pour des échanges ciblés et des synergies. Adhésion libre et
 * multiple (on peut suivre plusieurs pôles ou formations en parallèle).
 */
#[Layout('layouts.member-light')]
class Groupes extends Component
{
    public ?string $message = null;

    public function rejoindre(int $id): void
    {
        $result = Api::post("/groups/{$id}/join", [], Api::token());
        $this->message = ($result['ok'] ?? false) ? 'Vous avez rejoint le groupe !' : ($result['message'] ?? 'Une erreur est survenue.');
    }

    public function quitter(int $id): void
    {
        $result = Api::post("/groups/{$id}/leave", [], Api::token());
        $this->message = ($result['ok'] ?? false) ? 'Vous avez quitté le groupe.' : ($result['message'] ?? 'Une erreur est survenue.');
    }

    public function render()
    {
        $groups = collect(Api::get('/groups', [], Api::token())['groups'] ?? []);

        return view('livewire.member.groupes', [
            'groups' => $groups,
            'mesGroupes' => $groups->where('joined', true)->values(),
        ]);
    }
}
