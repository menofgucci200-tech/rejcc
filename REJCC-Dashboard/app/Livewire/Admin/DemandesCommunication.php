<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Demandes de communication des départements, traitées par la
 * Communication : liste (urgentes en premier), fiche, statuts horodatés,
 * refus motivé, réponses au demandeur et planification dans le calendrier
 * éditorial. Le demandeur est prévenu par e-mail à chaque étape.
 */
#[Layout('layouts.admin-light')]
class DemandesCommunication extends Component
{
    #[Url(as: 'statut', except: 'ouvertes')]
    public string $filtreStatut = 'ouvertes';

    #[Url(as: 'departement', except: '')]
    public string $filtreDepartement = '';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    public int $page = 1;

    #[Url(as: 'fiche', except: null)]
    public ?int $ouverte = null;

    /** Action en cours sur la fiche : '' | refuser | planifier | publier | commenter */
    public string $action = '';

    public string $motif = '';

    public string $commentaire = '';

    public string $datePublication = '';

    public string $heurePublication = '';

    public string $responsable = '';

    public string $lienPublication = '';

    public ?string $message = null;

    public ?string $erreur = null;

    public function updatedRecherche(): void
    {
        $this->page = 1;
    }

    public function updatedFiltreDepartement(): void
    {
        $this->page = 1;
    }

    public function filtrer(string $statut): void
    {
        $this->filtreStatut = $statut;
        $this->page = 1;
    }

    public function gotoPage(int $p): void
    {
        $this->page = max(1, $p);
    }

    public function ouvrir(int $id): void
    {
        $this->ouverte = $this->ouverte === $id ? null : $id;
        $this->fermerAction();
        $this->reset(['message', 'erreur']);
    }

    public function choisirAction(string $action): void
    {
        $this->action = $this->action === $action ? '' : $action;
        $this->reset(['motif', 'commentaire', 'lienPublication', 'heurePublication', 'responsable']);
        $this->resetValidation();
        if ($action === 'planifier' && ($f = $this->fiche())) {
            // Date proposée : celle souhaitée par le demandeur (ou la date déjà prévue).
            $this->datePublication = (string) ($f['date_publication_prevue'] ?? $f['date_souhaitee']);
            $this->responsable = (string) ($f['publication']['responsable_id'] ?? '');
        }
    }

    public function fermerAction(): void
    {
        $this->action = '';
        $this->reset(['motif', 'commentaire', 'datePublication', 'heurePublication', 'responsable', 'lienPublication']);
        $this->resetValidation();
    }

    private function fiche(): ?array
    {
        if (! $this->ouverte) {
            return null;
        }
        $r = Api::get("/admin/demandes-communication/{$this->ouverte}", [], Api::token());

        return ($r['ok'] ?? false) ? $r['demande'] : null;
    }

    private function terminer(array $r, string $succes): void
    {
        if ($r['ok'] ?? false) {
            $this->message = $succes;
            $this->erreur = null;
            $this->fermerAction();
            AdminNav::oublier(); // la pastille « À traiter » suit les statuts
        } else {
            $premiere = collect($r['errors'] ?? [])->flatten()->first();
            $this->erreur = $premiere ?: ($r['message'] ?? 'Action impossible.');
            $this->message = null;
        }
    }

    /** Accepter, repasser en « acceptée », ou toute transition sans saisie. */
    public function changerStatut(string $statut): void
    {
        if (! $this->ouverte) {
            return;
        }
        $r = Api::put("/admin/demandes-communication/{$this->ouverte}/statut", ['statut' => $statut, 'commentaire' => trim($this->commentaire) ?: null], Api::token());
        $this->terminer($r, match ($statut) {
            'acceptee' => 'Demande acceptée : le demandeur est prévenu par e-mail.',
            default => 'Statut mis à jour : le demandeur est prévenu par e-mail.',
        });
    }

    public function refuser(): void
    {
        $this->validate(['motif' => 'required|string|min:3|max:2000'], ['motif.required' => 'Indiquez le motif du refus : il sera transmis au demandeur.', 'motif.min' => 'Indiquez le motif du refus : il sera transmis au demandeur.']);
        $r = Api::put("/admin/demandes-communication/{$this->ouverte}/statut", ['statut' => 'refusee', 'motif' => trim($this->motif)], Api::token());
        $this->terminer($r, 'Demande refusée : le motif est transmis au demandeur.');
    }

    public function publier(): void
    {
        $this->validate(['lienPublication' => 'nullable|url:http,https|max:500'], ['lienPublication.url' => 'Collez le lien complet de la publication (https://…).']);
        $r = Api::put("/admin/demandes-communication/{$this->ouverte}/statut", [
            'statut' => 'publiee', 'lien_publication' => trim($this->lienPublication) ?: null, 'commentaire' => trim($this->commentaire) ?: null,
        ], Api::token());
        $this->terminer($r, 'Demande publiée : le demandeur est prévenu par e-mail.');
    }

    public function commenter(): void
    {
        $this->validate(['commentaire' => 'required|string|min:2|max:2000'], ['commentaire.required' => 'Écrivez votre message au demandeur.']);
        $r = Api::post("/admin/demandes-communication/{$this->ouverte}/commentaires", ['texte' => trim($this->commentaire)], Api::token());
        $this->terminer($r, 'Message envoyé au demandeur.');
    }

    /** Crée (ou déplace) la publication dans le calendrier éditorial et passe la demande à « planifiée ». */
    public function planifier(): void
    {
        $this->validate([
            'datePublication' => 'required|date',
            'heurePublication' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ], ['datePublication.required' => 'Choisissez la date de publication.', 'heurePublication.regex' => 'Heure au format HH:MM.']);
        $r = Api::post("/admin/demandes-communication/{$this->ouverte}/planifier", [
            'date' => $this->datePublication,
            'heure' => $this->heurePublication ?: null,
            'responsable_id' => ctype_digit($this->responsable) ? (int) $this->responsable : null,
            'commentaire' => trim($this->commentaire) ?: null,
        ], Api::token());
        $this->terminer($r, 'Publication ajoutée au calendrier éditorial : le demandeur est prévenu par e-mail.');
    }

    public function render()
    {
        $params = ['page' => $this->page, 'statut' => $this->filtreStatut];
        if ($this->filtreDepartement !== '') {
            $params['departement'] = $this->filtreDepartement;
        }
        if (trim($this->recherche) !== '') {
            $params['q'] = trim($this->recherche);
        }
        $r = Api::get('/admin/demandes-communication', $params, Api::token());
        $fiche = $this->fiche();
        $liste = $r['demandes'] ?? [];
        // La demande ouverte reste visible même si elle sort du filtre (publiée, refusée…) ou vient d'un lien.
        if ($fiche && ! collect($liste)->contains('id', $fiche['id'])) {
            array_unshift($liste, $fiche);
        }
        $equipe = $fiche && $this->action === 'planifier'
            ? (Api::get('/admin/cathoimpact/equipe', [], Api::token())['equipe'] ?? [])
            : [];

        return view('livewire.admin.demandes-communication', [
            'liste' => $liste,
            'meta' => $r['meta'] ?? [],
            'compteurs' => $r['compteurs'] ?? [],
            'statuts' => $r['statuts'] ?? [],
            'departements' => $r['departements'] ?? [],
            'fiche' => $fiche,
            'equipe' => $equipe,
            'plan' => Api::get('/admin/plan-diffusion', [], Api::token())['plan'] ?? null,
        ]);
    }
}
