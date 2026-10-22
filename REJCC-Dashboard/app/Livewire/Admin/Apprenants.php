<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Suivi des apprenants d'une formation (département Formation) : progression,
 * dernière activité, examen ; relance groupée par notification ou WhatsApp.
 */
#[Layout('layouts.admin-light')]
class Apprenants extends Component
{
    #[Locked]
    public int $formationId;

    #[Url(as: 'filtre', except: 'tous')]
    public string $filtre = 'tous';

    /** @var list<int> */
    public array $choisis = [];

    public string $messageRelance = '';

    public ?string $message = null;

    public function mount(int $formationId): void
    {
        $this->formationId = $formationId;
    }

    public function choisirTous(array $ids): void
    {
        $this->choisis = count($this->choisis) === count($ids) ? [] : array_map('intval', $ids);
    }

    public function relancer(): void
    {
        $r = Api::post("/admin/formations/{$this->formationId}/relance", ['user_ids' => $this->choisis, 'message' => $this->messageRelance], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->message = $r['message'] ?? 'Relance impossible.';

            return;
        }
        $this->message = ($r['envoyes'] ?? 0).' apprenant(s) relancé(s) par notification.';
        $this->choisis = [];
        $this->messageRelance = '';
    }

    public function render()
    {
        $r = Api::get("/admin/formations/{$this->formationId}/apprenants", [], Api::token());
        abort_unless($r['ok'] ?? false, 404, 'Formation introuvable.');
        $tous = collect($r['apprenants']);

        return view('livewire.admin.apprenants', [
            'formation' => $r['formation'],
            'apprenants' => $this->filtre === 'tous' ? $tous : $tous->where('statut', $this->filtre)->values(),
            'compteurs' => ['tous' => $tous->count()] + $tous->countBy('statut')->all(),
        ]);
    }
}
