<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\CategoryPalette;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Agenda du réseau : événements à venir, fiche complète, inscription
 * (règles appliquées par l'API : statut, date, capacité, abonnement).
 */
#[Layout('layouts.member-light')]
class Evenements extends Component
{
    /** Événement ouvert (lien partageable, accueil, recherche, notification). */
    #[Url(as: 'evenement', except: null)]
    public ?int $focus = null;

    public ?array $fiche = null;

    public ?string $message = null;

    public ?string $erreur = null;

    public function mount(): void
    {
        if ($this->focus) {
            $this->voir($this->focus);
        }
    }

    public function voir(int $id): void
    {
        $result = Api::get("/events/{$id}", [], Api::token());
        $this->erreur = null;
        if ($result['ok'] ?? false) {
            $this->fiche = $result['event'];
            $this->focus = $id;
        } else {
            $this->fiche = null;
            $this->focus = null;
            $this->message = $result['message'] ?? 'Événement introuvable.';
        }
    }

    public function fermerFiche(): void
    {
        $this->fiche = null;
        $this->focus = null;
        $this->erreur = null;
    }

    public function inscrire(int $id): void
    {
        $result = Api::post("/events/{$id}/inscription", [], Api::token());
        $this->apresAction($id, $result, 'Inscription confirmée : vous la retrouvez dans vos notifications et dans « Mes inscriptions ».');
    }

    public function desinscrire(int $id): void
    {
        $result = Api::delete("/events/{$id}/inscription", Api::token());
        $this->apresAction($id, $result, 'Votre inscription est annulée.');
    }

    private function apresAction(int $id, array $result, string $succes): void
    {
        if ($result['ok'] ?? false) {
            $this->message = $succes;
            $this->erreur = null;
        } else {
            $this->erreur = $result['message'] ?? 'Une erreur est survenue.';
            $this->message = null;
        }
        if ($this->fiche && $this->fiche['id'] === $id) {
            $this->voir($id);
            if (! ($result['ok'] ?? false)) {
                $this->erreur = $result['message'] ?? 'Une erreur est survenue.';
            }
        }
    }

    protected function evenements(): Collection
    {
        return Collection::make(Api::get('/events', [], Api::token())['events'] ?? [])
            ->map(function (array $e) {
                $e['debut'] = Carbon::parse($e['starts_at'])->setTimezone(config('app.timezone'))->locale('fr');
                $e['couleur'] = CategoryPalette::for($e['category'])['tag'];

                return $e;
            });
    }

    public function render()
    {
        $now = Carbon::now();
        $all = $this->evenements();

        $avenir = $all->filter(fn (array $e) => ! $e['passe'])->sortBy('starts_at')->values();

        $eventDays = $all
            ->filter(fn (array $e) => $e['debut']->isSameMonth($now))
            ->map(fn (array $e) => $e['debut']->day)
            ->values()
            ->all();

        $firstOfMonth = $now->copy()->startOfMonth();
        $cells = array_fill(0, $firstOfMonth->dayOfWeekIso - 1, null);
        for ($d = 1; $d <= $now->daysInMonth; $d++) {
            $cells[] = $d;
        }

        return view('livewire.member.evenements', [
            'evenements' => $avenir,
            'cells' => $cells,
            'eventDays' => $eventDays,
            'today' => $now->day,
            'moisLabel' => ucfirst($now->translatedFormat('F Y')),
        ]);
    }
}
