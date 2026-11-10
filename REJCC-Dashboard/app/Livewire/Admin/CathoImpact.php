<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\AdminNav;
use App\Support\Api;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * CathoImpact dans l'espace Communication : réponses au formulaire du site
 * (présélection, suivi jusqu'à la publication) et galerie « Ils ont inspiré »
 * de la page /cathoimpact.
 */
#[Layout('layouts.admin-light')]
class CathoImpact extends Component
{
    use HandlesMedia;

    /** Chemin d'une photo déposée par le site (même contrôle que l'API). */
    private const CHEMIN_PHOTO = '/^cathoimpact\/\d{4}\/\d{2}\/[0-9a-f-]{36}\.(jpg|jpeg|png|webp)$/';

    #[Url(as: 'onglet', except: 'reponses')]
    public string $onglet = 'reponses'; // reponses | galerie

    // ── Réponses ────────────────────────────────────────────────────────
    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    #[Url(as: 'statut', except: '')]
    public string $filtreStatut = '';

    public int $page = 1;

    /** Réponse ouverte (fiche) et son suivi en cours de modification. */
    #[Url(as: 'fiche', except: null)]
    public ?int $ouverte = null;

    public string $statut = '';

    /** id d'un compte de l'équipe, « libre » (nom saisi) ou vide. */
    public string $cm = '';

    public string $cmLibre = '';

    public string $lienQuestionnaire = '';

    public string $lienPublication = '';

    public string $note = '';

    public ?string $message = null;

    public ?string $erreur = null;

    // ── Galerie ─────────────────────────────────────────────────────────
    public bool $carteForm = false;

    public ?int $carteId = null;

    public string $carteNom = '';

    public string $carteAccroche = '';

    public string $carteLien = '';

    public string $carteReseau = 'instagram';

    public string $carteCandidature = '';

    public bool $carteVisible = true;

    public function changerOnglet(string $onglet): void
    {
        $this->onglet = in_array($onglet, ['reponses', 'galerie'], true) ? $onglet : 'reponses';
        $this->reset(['message', 'erreur', 'carteForm']);
    }

    public function updatedRecherche(): void
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
        $this->reset(['message', 'erreur']);
        $this->resetValidation();
        if ($this->ouverte === $id) {
            $this->ouverte = null;

            return;
        }
        $this->ouverte = $id;
        $this->remplirSuivi();
    }

    /** Fiche de la réponse ouverte (null si introuvable). */
    private function fiche(): ?array
    {
        if (! $this->ouverte) {
            return null;
        }
        $r = Api::get("/admin/cathoimpact/{$this->ouverte}", [], Api::token());

        return ($r['ok'] ?? false) ? $r['candidature'] : null;
    }

    private function remplirSuivi(): void
    {
        $f = $this->fiche();
        if (! $f) {
            $this->ouverte = null;

            return;
        }
        $this->statut = $f['statut'];
        $this->cm = $f['community_manager_id'] ? (string) $f['community_manager_id'] : ($f['community_manager_libre'] ? 'libre' : '');
        $this->cmLibre = (string) ($f['community_manager_libre'] ?? '');
        $this->lienQuestionnaire = (string) ($f['lien_questionnaire'] ?? '');
        $this->lienPublication = (string) ($f['lien_publication'] ?? '');
        $this->note = (string) ($f['note_interne'] ?? '');
    }

    public function enregistrer(): void
    {
        if (! $this->ouverte) {
            return;
        }
        $this->validate([
            'cmLibre' => 'nullable|string|max:150',
            'lienQuestionnaire' => 'nullable|url:http,https|max:500',
            'lienPublication' => 'nullable|url:http,https|max:500',
            'note' => 'nullable|string|max:5000',
        ], [
            'lienQuestionnaire.url' => 'Collez le lien complet du formulaire (https://…).',
            'lienPublication.url' => 'Collez le lien complet de la publication (https://…).',
        ]);

        $r = Api::put("/admin/cathoimpact/{$this->ouverte}", [
            'statut' => $this->statut,
            'community_manager_id' => ctype_digit($this->cm) ? (int) $this->cm : null,
            'community_manager' => $this->cm === 'libre' ? (trim($this->cmLibre) ?: null) : null,
            'lien_questionnaire' => trim($this->lienQuestionnaire) ?: null,
            'lien_publication' => trim($this->lienPublication) ?: null,
            'note_interne' => trim($this->note) ?: null,
        ], Api::token());

        if ($r['ok'] ?? false) {
            $this->message = 'Suivi enregistré.';
            $this->erreur = null;
            AdminNav::oublier(); // la pastille « À traiter » change avec le statut
            $this->remplirSuivi();
        } else {
            $this->erreur = $r['message'] ?? 'Enregistrement impossible.';
            $this->message = null;
        }
    }

    public function supprimer(int $id): void
    {
        $r = Api::delete("/admin/cathoimpact/{$id}", Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Suppression impossible.';

            return;
        }
        // La photo est dans le stockage privé partagé avec le site.
        $photo = (string) ($r['photo'] ?? '');
        if ($photo !== '' && preg_match(self::CHEMIN_PHOTO, $photo)) {
            Storage::disk('local')->delete($photo);
        }
        $this->ouverte = null;
        $this->message = 'Réponse supprimée, photo comprise.';
        AdminNav::oublier();
    }

    // ── Galerie ─────────────────────────────────────────────────────────

    private function portraits(): array
    {
        return Api::get('/admin/cathoimpact/portraits', [], Api::token());
    }

    public function nouvelleCarte(): void
    {
        $this->reset(['carteId', 'carteNom', 'carteAccroche', 'carteLien', 'carteCandidature', 'message', 'erreur']);
        $this->carteReseau = 'instagram';
        $this->carteVisible = true;
        $this->clearMedia();
        $this->resetValidation();
        $this->carteForm = true;
    }

    public function modifierCarte(int $id): void
    {
        $p = collect($this->portraits()['portraits'] ?? [])->firstWhere('id', $id);
        if (! $p) {
            return;
        }
        $this->reset(['message', 'erreur']);
        $this->carteId = $p['id'];
        $this->carteNom = $p['nom'];
        $this->carteAccroche = (string) ($p['accroche'] ?? '');
        $this->carteLien = $p['lien'];
        $this->carteReseau = $p['reseau'];
        $this->carteCandidature = $p['candidature_id'] ? (string) $p['candidature_id'] : '';
        $this->carteVisible = (bool) $p['visible'];
        $this->fillMedia($p['image_url']);
        $this->resetValidation();
        $this->carteForm = true;
    }

    public function fermerCarte(): void
    {
        $this->carteForm = false;
        $this->carteId = null;
    }

    public function enregistrerCarte(): void
    {
        $this->validate([
            'carteNom' => 'required|string|min:2|max:150',
            'carteAccroche' => 'nullable|string|max:200',
            'carteLien' => 'required|url:http,https|max:500',
            'mediaUrl' => ['required', 'url:http,https', 'max:500', 'not_regex:/\.(pdf|docx?|pptx?|xlsx?|csv|txt|mp4|webm|mov|mp3|wav)(\?.*)?$/i'],
        ], [
            'carteNom.required' => 'Indiquez le nom de la personne.',
            'carteLien.required' => 'Collez le lien de la publication.',
            'carteLien.url' => 'Collez le lien complet de la publication (https://…).',
            'mediaUrl.required' => 'Ajoutez une image.',
            'mediaUrl.url' => 'L\'image doit être un fichier téléversé ou un lien https://',
            'mediaUrl.not_regex' => 'Choisissez une image (JPG, PNG ou WebP).',
        ]);

        $donnees = [
            'nom' => trim($this->carteNom),
            'accroche' => trim($this->carteAccroche) ?: null,
            'lien' => trim($this->carteLien),
            'reseau' => $this->carteReseau,
            'image_url' => $this->mediaUrl,
            'candidature_id' => ctype_digit($this->carteCandidature) ? (int) $this->carteCandidature : null,
            'visible' => $this->carteVisible,
        ];
        $r = $this->carteId
            ? Api::put("/admin/cathoimpact/portraits/{$this->carteId}", $donnees, Api::token())
            : Api::post('/admin/cathoimpact/portraits', $donnees, Api::token());

        if ($r['ok'] ?? false) {
            $this->message = $this->carteId ? 'Carte modifiée.' : 'Carte ajoutée à la galerie du site.';
            $this->erreur = null;
            $this->fermerCarte();
        } else {
            $this->erreur = $r['message'] ?? 'Enregistrement impossible.';
        }
    }

    public function basculerVisible(int $id, bool $visible): void
    {
        Api::put("/admin/cathoimpact/portraits/{$id}", ['visible' => ! $visible], Api::token());
    }

    /** Monte (-1) ou descend (+1) une carte dans la galerie. */
    public function deplacer(int $id, int $sens): void
    {
        $ids = collect($this->portraits()['portraits'] ?? [])->pluck('id')->values()->all();
        $i = array_search($id, $ids, true);
        $j = $i === false ? false : $i + ($sens < 0 ? -1 : 1);
        if ($i === false || $j < 0 || $j >= count($ids)) {
            return;
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        Api::put('/admin/cathoimpact/portraits/ordre', ['ids' => $ids], Api::token());
    }

    public function supprimerCarte(int $id): void
    {
        Api::delete("/admin/cathoimpact/portraits/{$id}", Api::token());
        if ($this->carteId === $id) {
            $this->fermerCarte();
        }
        $this->message = 'Carte retirée de la galerie.';
    }

    public function render()
    {
        $donnees = ['fiche' => null, 'equipe' => [], 'liste' => [], 'meta' => [], 'compteurs' => [], 'statuts' => [], 'portraits' => [], 'reseaux' => [], 'choixCandidatures' => []];

        if ($this->onglet === 'galerie') {
            $g = $this->portraits();
            $donnees['portraits'] = $g['portraits'] ?? [];
            $donnees['reseaux'] = $g['reseaux'] ?? [];
            if ($this->carteForm) {
                $donnees['choixCandidatures'] = Api::get('/admin/cathoimpact', ['par_page' => 100], Api::token())['candidatures'] ?? [];
            }
        } else {
            $params = ['page' => $this->page];
            if (trim($this->recherche) !== '') {
                $params['q'] = trim($this->recherche);
            }
            if ($this->filtreStatut !== '') {
                $params['statut'] = $this->filtreStatut;
            }
            $r = Api::get('/admin/cathoimpact', $params, Api::token());
            $donnees['liste'] = $r['candidatures'] ?? [];
            $donnees['meta'] = $r['meta'] ?? [];
            $donnees['compteurs'] = $r['compteurs'] ?? [];
            $donnees['statuts'] = $r['statuts'] ?? [];
            if ($this->ouverte) {
                $donnees['fiche'] = $this->fiche();
                $donnees['equipe'] = Api::get('/admin/cathoimpact/equipe', [], Api::token())['equipe'] ?? [];
                if ($donnees['fiche'] && $this->statut === '') {
                    $this->remplirSuivi(); // fiche ouverte par l'adresse (?fiche=)
                }
            }
        }

        return view('livewire.admin.cathoimpact', $donnees);
    }
}
