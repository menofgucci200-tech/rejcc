<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Conversations signalées par les membres. Une conversation privée n'est
 * visible ici que parce que l'un de ses participants l'a signalée.
 */
#[Layout('layouts.admin-light')]
class Signalements extends Component
{
    public string $statut = 'nouveau';

    public int $page = 1;

    public ?int $ouvertId = null;

    public ?string $message = null;

    public function updatedStatut(): void
    {
        $this->page = 1;
        $this->ouvertId = null;
    }

    public function gotoPage(int $p): void
    {
        $this->page = max(1, $p);
    }

    public function ouvrir(int $id): void
    {
        $this->ouvertId = $id;
        $this->message = null;
    }

    public function fermer(): void
    {
        $this->ouvertId = null;
    }

    public function traiter(string $decision): void
    {
        if (! $this->ouvertId) {
            return;
        }
        $result = Api::put("/admin/signalements-messages/{$this->ouvertId}", ['decision' => $decision], Api::token());
        AdminNav::oublier();
        $this->ouvertId = null;
        $this->message = ($result['ok'] ?? false)
            ? ($decision === 'averti' ? 'Le membre a reçu un avertissement ; l\'auteur du signalement est informé.' : 'Signalement classé sans suite ; son auteur est informé.')
            : ($result['message'] ?? 'Une erreur est survenue.');
    }

    public function render()
    {
        $liste = Api::get('/admin/signalements-messages', ['statut' => $this->statut, 'page' => $this->page], Api::token());
        $detail = $this->ouvertId ? Api::get("/admin/signalements-messages/{$this->ouvertId}", [], Api::token()) : null;

        return view('livewire.admin.signalements', [
            'ok' => $liste['ok'] ?? false,
            'signalements' => $liste['signalements'] ?? [],
            'meta' => $liste['meta'] ?? [],
            'detail' => ($detail['ok'] ?? false) ? $detail : null,
        ]);
    }
}
