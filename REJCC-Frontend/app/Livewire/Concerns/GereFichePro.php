<?php

namespace App\Livewire\Concerns;

use App\Support\Api;

/**
 * Fiche professionnelle d'un membre dans un groupe sectoriel (fenêtre
 * <x-groupes.fiche-pro>) et avis des membres : ouverture, note, retrait.
 * Utilisé par le trombinoscope d'un groupe et par la recherche « Je cherche… ».
 */
trait GereFichePro
{
    public ?array $detail = null;

    public ?string $avisErreur = null;

    /** Ouvre la fiche professionnelle du membre dans le groupe indiqué. */
    public function voirFiche(int $groupId, int $id): void
    {
        $result = Api::get("/groups/{$groupId}/members/{$id}", [], Api::token());
        $this->detail = ($result['ok'] ?? false) ? $result['fiche'] : null;
        $this->avisErreur = null;
    }

    public function fermerProfil(): void
    {
        $this->detail = null;
        $this->avisErreur = null;
    }

    /** Donne ou modifie son avis (note 1-5 + commentaire) sur le membre affiché. */
    public function noter(int $note, string $commentaire = ''): void
    {
        if (! $this->detail) {
            return;
        }
        $result = Api::post("/members/{$this->detail['membre']['id']}/avis", [
            'note' => $note,
            'commentaire' => $commentaire,
            'group_id' => $this->detail['groupe']['id'] ?? null,
        ], Api::token());

        if ($result['ok'] ?? false) {
            $this->detail['avis'] = $result['avis'];
            $this->avisErreur = null;
        } else {
            $this->avisErreur = $result['message'] ?? "Impossible d'enregistrer votre avis.";
        }
    }

    public function retirerAvis(): void
    {
        if (! $this->detail) {
            return;
        }
        $result = Api::delete("/members/{$this->detail['membre']['id']}/avis", Api::token());
        if ($result['ok'] ?? false) {
            $this->detail['avis'] = $result['avis'];
        }
    }
}
