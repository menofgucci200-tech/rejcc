<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Suivi d'un mentorat : objectif, séances (proposer, confirmer, annuler)
 * et comptes rendus rédigés par le mentor après chaque séance.
 */
#[Layout('layouts.member-light')]
class MentoratSuivi extends Component
{
    public int $mentorshipId;

    // Proposer une séance
    public bool $formSeance = false;

    public string $debut = '';

    public int $duree = 60;

    public string $format = 'visio';

    public string $lieu = '';

    public string $ordreDuJour = '';

    // Compte rendu (mentor)
    public ?int $compteRenduPour = null;

    public string $compteRendu = '';

    public string $prochainesEtapes = '';

    // Clôture et évaluation
    public bool $formFin = false;

    public string $bilan = '';

    public int $note = 0;

    public string $avis = '';

    public ?string $message = null;

    public ?string $erreur = null;

    public function mount(int $mentorshipId): void
    {
        $this->mentorshipId = $mentorshipId;
    }

    protected function resultat(array $result, string $succes): bool
    {
        $this->message = $this->erreur = null;
        if (! ($result['ok'] ?? false)) {
            $this->erreur = $result['message'] ?? 'Action impossible, réessayez.';

            return false;
        }
        \App\Support\NavCompteurs::oublier();
        $this->message = $succes;

        return true;
    }

    public function ouvrirFormSeance(string $formatParDefaut = 'visio'): void
    {
        $this->formSeance = true;
        $this->format = $formatParDefaut === 'presentiel' ? 'presentiel' : 'visio';
        $this->erreur = null;
    }

    public function proposer(): void
    {
        $ok = $this->resultat(Api::post("/mentorat/{$this->mentorshipId}/seances", [
            'debut' => $this->debut,
            'duree_minutes' => $this->duree,
            'format' => $this->format,
            'lieu' => trim($this->lieu) ?: null,
            'ordre_du_jour' => trim($this->ordreDuJour) ?: null,
        ], Api::token()), 'Séance proposée : une notification a été envoyée pour confirmation.');

        if ($ok) {
            $this->reset(['formSeance', 'debut', 'lieu', 'ordreDuJour']);
            $this->duree = 60;
        }
    }

    public function confirmer(int $id): void
    {
        $this->resultat(Api::post("/seances/{$id}/confirmer", [], Api::token()), 'Séance confirmée.');
    }

    public function annuler(int $id, ?string $motif = null): void
    {
        $this->resultat(Api::post("/seances/{$id}/annuler", ['motif' => $motif], Api::token()), 'Séance annulée : une notification a été envoyée.');
    }

    public function ouvrirCompteRendu(int $id, ?string $texte = null, ?string $etapes = null): void
    {
        $this->compteRenduPour = $id;
        $this->compteRendu = (string) $texte;
        $this->prochainesEtapes = (string) $etapes;
        $this->erreur = null;
    }

    public function enregistrerCompteRendu(): void
    {
        if (! $this->compteRenduPour) {
            return;
        }
        $ok = $this->resultat(Api::post("/seances/{$this->compteRenduPour}/compte-rendu", [
            'compte_rendu' => trim($this->compteRendu),
            'prochaines_etapes' => trim($this->prochainesEtapes) ?: null,
        ], Api::token()), 'Compte rendu partagé.');

        if ($ok) {
            $this->reset(['compteRenduPour', 'compteRendu', 'prochainesEtapes']);
        }
    }

    public function terminer(): void
    {
        if ($this->resultat(Api::post("/mentorat/{$this->mentorshipId}/terminer", ['bilan' => trim($this->bilan) ?: null], Api::token()), 'Mentorat terminé : une notification a été envoyée.')) {
            $this->reset(['formFin', 'bilan']);
        }
    }

    public function evaluer(): void
    {
        if ($this->resultat(Api::post("/mentorat/{$this->mentorshipId}/evaluer", ['note' => $this->note ?: null, 'avis' => trim($this->avis) ?: null], Api::token()), 'Merci pour votre avis !')) {
            $this->reset(['note', 'avis']);
        }
    }

    public function render()
    {
        $result = Api::get("/mentorat/{$this->mentorshipId}", [], Api::token());
        $mentorat = $result['mentorat'] ?? null;
        $seances = Collection::make($mentorat['seances'] ?? []);

        return view('livewire.member.mentorat-suivi', [
            'mentorat' => $mentorat,
            'aVenir' => $seances->filter(fn ($s) => in_array($s['statut'], ['proposee', 'confirmee'], true) && ! $s['passee'])->values(),
            // Séances passées : confirmées (en attente de compte rendu) ou réalisées, les plus récentes d'abord.
            'passees' => $seances->filter(fn ($s) => $s['statut'] === 'realisee' || ($s['statut'] === 'confirmee' && $s['passee']))->reverse()->values(),
            'annulees' => $seances->filter(fn ($s) => $s['statut'] === 'annulee' || ($s['statut'] === 'proposee' && $s['passee']))->values(),
        ]);
    }
}
