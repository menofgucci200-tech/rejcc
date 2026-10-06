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

    /** Onglet : avenir | mes | passes. */
    #[Url(except: 'avenir')]
    public string $onglet = 'avenir';

    #[Url(as: 'categorie', except: '')]
    public string $categorie = '';

    /** Mois affiché dans le calendrier (AAAA-MM). */
    public string $mois = '';

    /** Jour sélectionné dans le calendrier (AAAA-MM-JJ) : filtre la liste. */
    public ?string $jour = null;

    public ?array $fiche = null;

    public ?string $message = null;

    public ?string $erreur = null;

    public function mount(): void
    {
        $this->mois = now()->format('Y-m');
        if ($this->focus) {
            $this->voir($this->focus);
        }
    }

    public function setOnglet(string $onglet): void
    {
        $this->onglet = in_array($onglet, ['avenir', 'mes', 'passes'], true) ? $onglet : 'avenir';
        $this->jour = null;
    }

    public function moisPrecedent(): void
    {
        $this->mois = Carbon::createFromFormat('Y-m-d', $this->mois.'-01')->subMonth()->format('Y-m');
    }

    public function moisSuivant(): void
    {
        $this->mois = Carbon::createFromFormat('Y-m-d', $this->mois.'-01')->addMonth()->format('Y-m');
    }

    /** Clic sur un jour du calendrier : n'affiche que les événements de ce jour. */
    public function choisirJour(string $jour): void
    {
        $this->jour = $this->jour === $jour ? null : $jour;
        if ($this->jour) {
            $this->onglet = Carbon::parse($jour)->endOfDay()->isPast() ? 'passes' : 'avenir';
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
        $all = $this->evenements();
        $categories = $all->pluck('category')->unique()->sort()->values();

        $liste = match ($this->onglet) {
            'mes' => $all->filter(fn ($e) => $e['registered'])->sortBy(fn ($e) => [$e['passe'] ? 1 : 0, $e['passe'] ? -$e['debut']->timestamp : $e['debut']->timestamp]),
            'passes' => $all->filter(fn ($e) => $e['passe'])->sortByDesc('starts_at'),
            default => $all->filter(fn ($e) => ! $e['passe'])->sortBy('starts_at'),
        };
        if ($this->categorie !== '') {
            $liste = $liste->filter(fn ($e) => $e['category'] === $this->categorie);
        }
        if ($this->jour) {
            $liste = $liste->filter(fn ($e) => $e['debut']->toDateString() === $this->jour);
        }

        // Calendrier du mois choisi : jours avec événement(s), dont ceux où je suis inscrit.
        $mois = Carbon::createFromFormat('Y-m-d', ($this->mois ?: now()->format('Y-m')).'-01')->locale('fr');
        $duMois = $all->filter(fn ($e) => $e['debut']->isSameMonth($mois) && $e['debut']->isSameYear($mois));
        $jours = [];
        foreach ($duMois as $e) {
            $d = $e['debut']->toDateString();
            $jours[$d] = ($jours[$d] ?? false) || $e['registered'];
        }
        $cells = array_fill(0, $mois->copy()->startOfMonth()->dayOfWeekIso - 1, null);
        for ($d = 1; $d <= $mois->daysInMonth; $d++) {
            $cells[] = $mois->copy()->day($d)->toDateString();
        }

        return view('livewire.member.evenements', [
            'evenements' => $liste->values(),
            'categories' => $categories,
            'compteurs' => [
                'avenir' => $all->filter(fn ($e) => ! $e['passe'])->count(),
                'mes' => $all->filter(fn ($e) => $e['registered'] && ! $e['passe'])->count(),
                'passes' => $all->filter(fn ($e) => $e['passe'])->count(),
            ],
            'cells' => $cells,
            'joursEvenements' => $jours,
            'aujourdhui' => now()->toDateString(),
            'moisLabel' => ucfirst($mois->isoFormat('MMMM YYYY')),
        ]);
    }
}
