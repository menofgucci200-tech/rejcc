<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Pilotage du mentorat : mentors (charge, avis), relations en cours,
 * candidatures « Devenir mentor » et attribution manuelle d'un mentor.
 */
#[Layout('layouts.admin-light')]
class Mentors extends Component
{
    #[Url(as: 'onglet', except: 'mentors')]
    public string $onglet = 'mentors';

    public string $filtreStatut = 'tous';

    // Attribution manuelle
    public bool $formAttribution = false;

    public ?int $mentorId = null;

    public ?int $mentoreId = null;

    public string $objectif = '';

    /** @var array<int, string> Message de l'équipe par candidature. */
    public array $reponses = [];

    public ?string $message = null;

    public ?string $erreur = null;

    public function setOnglet(string $onglet): void
    {
        $this->onglet = $onglet;
        $this->message = $this->erreur = null;
    }

    protected function resultat(array $result, string $succes): bool
    {
        $this->message = $this->erreur = null;
        if (! ($result['ok'] ?? false)) {
            $this->erreur = $result['message'] ?? 'Action impossible, réessayez.';

            return false;
        }
        $this->message = $succes;

        return true;
    }

    public function ouvrirAttribution(?int $mentorId = null): void
    {
        $this->formAttribution = true;
        $this->mentorId = $mentorId;
        $this->message = $this->erreur = null;
    }

    public function attribuer(): void
    {
        if ($this->resultat(Api::post('/admin/mentorat/attribuer', [
            'mentor_id' => $this->mentorId,
            'mentore_id' => $this->mentoreId,
            'objectif' => trim($this->objectif),
        ], Api::token()), 'Mentorat créé : le mentor et le membre sont notifiés.')) {
            $this->reset(['formAttribution', 'mentorId', 'mentoreId', 'objectif']);
            $this->onglet = 'relations';
        }
    }

    public function accepterCandidature(int $id): void
    {
        if ($this->resultat(Api::post("/admin/mentorat/candidatures/{$id}/accepter", ['reponse' => trim($this->reponses[$id] ?? '') ?: null], Api::token()), 'Candidature acceptée : le membre devient mentor.')) {
            unset($this->reponses[$id]);
        }
    }

    public function refuserCandidature(int $id): void
    {
        if ($this->resultat(Api::post("/admin/mentorat/candidatures/{$id}/refuser", ['reponse' => trim($this->reponses[$id] ?? '')], Api::token()), 'Réponse envoyée au membre.')) {
            unset($this->reponses[$id]);
        }
    }

    public function render()
    {
        $data = Api::get('/admin/mentorat', [], Api::token());
        $relations = Collection::make($data['relations'] ?? []);

        return view('livewire.admin.mentors', [
            'ok' => $data['ok'] ?? false,
            'stats' => $data['stats'] ?? [],
            'mentors' => Collection::make($data['mentors'] ?? []),
            'relations' => $this->filtreStatut === 'tous' ? $relations : $relations->where('statut', $this->filtreStatut)->values(),
            'candidatures' => Collection::make($data['candidatures'] ?? []),
            'membres' => Collection::make($data['membres'] ?? []),
        ]);
    }
}
