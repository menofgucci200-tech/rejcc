<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Pointage le jour J : scan du billet (QR) ou de la carte membre à
 * l'accueil, saisie manuelle possible, inscription sur place.
 */
#[Layout('layouts.admin-light')]
class EvenementPointage extends Component
{
    public int $eventId;

    public string $code = '';

    public ?array $resultat = null;

    public string $filtre = '';

    public function mount(int $id): void
    {
        $this->eventId = $id;
    }

    public function pointer(string $code = '', bool $surPlace = false): void
    {
        $code = trim($code !== '' ? $code : $this->code);
        if ($code === '') {
            return;
        }
        $r = Api::post("/admin/events/{$this->eventId}/pointage", ['code' => $code, 'sur_place' => $surPlace], Api::token());
        $this->resultat = $r + ['code_scanne' => $code];
        $this->code = '';
    }

    public function render()
    {
        $data = Api::get("/admin/events/{$this->eventId}/inscrits", [], Api::token());
        $inscrits = collect($data['inscrits'] ?? []);
        $q = mb_strtolower(trim($this->filtre));
        if ($q !== '') {
            $inscrits = $inscrits->filter(fn ($i) => str_contains(mb_strtolower($i['nom'].' '.$i['billet'].' '.$i['email'].' '.$i['telephone']), $q));
        }

        return view('livewire.admin.evenement-pointage', [
            'ok' => $data['ok'] ?? false,
            'evenement' => $data['event'] ?? null,
            'inscrits' => $inscrits->sortBy(fn ($i) => [$i['present_at'] ? 1 : 0, $i['nom']])->values(),
            'nbInscrits' => count($data['inscrits'] ?? []),
            'presents' => (int) ($data['presents'] ?? 0),
        ]);
    }
}
