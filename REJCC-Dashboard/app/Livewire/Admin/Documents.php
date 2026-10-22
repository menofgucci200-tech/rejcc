<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Bibliothèque de documents : envoi du fichier (stocké en privé, servi
 * après contrôle d'accès), catégories gérées, accès par document,
 * propositions des membres à valider, statistiques.
 */
#[Layout('layouts.admin-light')]
class Documents extends Component
{
    use WithFileUploads;

    #[Url(except: '')]
    public string $filtre = '';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    public ?string $message = null;

    public ?string $erreur = null;

    // Formulaire
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $categoryId = '';

    public string $acces = 'tous';

    public string $groupId = '';

    public string $lien = '';

    public bool $notifier = true;

    /** Fichier envoyé (temporaire), puis stocké en privé. */
    public $fichierUpload = null;

    public ?array $fichier = null; // ['chemin', 'nom', 'mime', 'octets']

    // Catégories
    public bool $gererCategories = false;

    public string $nouvelleCategorie = '';

    public ?int $categorieEditee = null;

    public string $nomCategorie = '';

    // Refus d'une proposition
    public ?int $refusId = null;

    public string $motif = '';

    public function setFiltre(string $f): void
    {
        $this->filtre = in_array($f, ['', 'publie', 'en_attente', 'refuse'], true) ? $f : '';
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'description', 'categoryId', 'groupId', 'lien', 'fichierUpload', 'fichier', 'erreur', 'message']);
        $this->acces = 'tous';
        $this->notifier = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $d = collect(Api::get('/admin/documents', [], Api::token())['documents'] ?? [])->firstWhere('id', $id);
        if (! $d) {
            return;
        }
        $this->editingId = $id;
        $this->title = $d['title'];
        $this->description = (string) $d['description'];
        $this->categoryId = (string) ($d['categorie']['id'] ?? '');
        $this->acces = $d['acces'];
        $this->groupId = (string) ($d['groupe']['id'] ?? '');
        $this->lien = (string) ($d['lien'] ?? '');
        $this->fichier = $d['fichier'] ? ['chemin' => $d['fichier'], 'nom' => $d['fichier_nom'] ?: basename($d['fichier']), 'mime' => null, 'octets' => null, 'existant' => true, 'taille' => $d['taille']] : null;
        $this->notifier = false;
        $this->fichierUpload = null;
        $this->erreur = $this->message = null;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function updatedFichierUpload(): void
    {
        $this->validate(['fichierUpload' => 'file|max:51200|mimes:pdf,doc,docx,odt,xls,xlsx,ods,csv,ppt,pptx,odp,txt,jpg,jpeg,png,gif,webp,mp4,webm,mov,mp3,wav,ogg,m4a,zip'], [
            'fichierUpload.max' => 'Le fichier ne doit pas dépasser 50 Mo.',
            'fichierUpload.mimes' => 'Format non pris en charge (PDF, Word, Excel, PowerPoint, image, vidéo, audio, ZIP).',
        ]);
        $nom = $this->fichierUpload->getClientOriginalName();
        $base = Str::slug(pathinfo($nom, PATHINFO_FILENAME)) ?: 'document';
        $chemin = $this->fichierUpload->storeAs('documents/'.date('Y/m'), $base.'-'.Str::random(6).'.'.strtolower($this->fichierUpload->getClientOriginalExtension()), 'local');
        $this->fichier = ['chemin' => $chemin, 'nom' => $nom, 'mime' => $this->fichierUpload->getMimeType(), 'octets' => $this->fichierUpload->getSize(), 'existant' => false];
        $this->fichierUpload = null;
        $this->lien = '';
    }

    public function retirerFichier(): void
    {
        if ($this->fichier && ! ($this->fichier['existant'] ?? false)) {
            Storage::disk('local')->delete($this->fichier['chemin']);
        }
        $this->fichier = null;
    }

    public function save(): void
    {
        $data = [
            'title' => trim($this->title), 'description' => trim($this->description) ?: null,
            'category_id' => $this->categoryId ? (int) $this->categoryId : null,
            'acces' => $this->acces, 'group_id' => $this->acces === 'groupe' && $this->groupId ? (int) $this->groupId : null,
            'notifier' => $this->notifier,
        ];
        if ($this->fichier) {
            $data += ['fichier' => $this->fichier['chemin'], 'fichier_nom' => $this->fichier['nom']];
            if (! ($this->fichier['existant'] ?? false)) {
                $data += ['mime' => $this->fichier['mime'], 'octets' => $this->fichier['octets']];
            }
        } else {
            $data['url'] = trim($this->lien) ?: null;
        }
        if ($this->editingId && ($this->fichier['existant'] ?? false)) {
            // Fichier inchangé : on renvoie ses informations telles quelles.
            $actuel = collect(Api::get('/admin/documents', [], Api::token())['documents'] ?? [])->firstWhere('id', $this->editingId);
            $data['fichier_nom'] = $actuel['fichier_nom'] ?? $data['fichier_nom'];
        }

        $r = $this->editingId
            ? Api::put("/admin/documents/{$this->editingId}", $data, Api::token())
            : Api::post('/admin/documents', $data, Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? "L'enregistrement a échoué.";

            return;
        }
        if (! empty($r['ancien_fichier'])) {
            Storage::disk('local')->delete($r['ancien_fichier']);
        }
        $n = (int) ($r['notifies'] ?? 0);
        $this->message = ($this->editingId ? 'Document mis à jour.' : 'Document publié.').($n ? " {$n} membre".($n > 1 ? 's' : '').' prévenu'.($n > 1 ? 's' : '').'.' : '');
        $this->erreur = null;
        $this->closeForm();
    }

    public function delete(int $id): void
    {
        $r = Api::delete("/admin/documents/{$id}", Api::token());
        if (! empty($r['fichier'])) {
            Storage::disk('local')->delete($r['fichier']);
        }
        $this->message = 'Document supprimé.';
        AdminNav::oublier();
    }

    // ── Propositions des membres ─────────────────────────────────────────

    public function publier(int $id): void
    {
        $r = Api::post("/admin/documents/{$id}/decision", ['decision' => 'publier', 'notifier' => true], Api::token());
        $this->message = ($r['ok'] ?? false) ? 'Document publié : le contributeur est remercié et les membres sont prévenus.' : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Une erreur est survenue.');
        AdminNav::oublier();
    }

    public function ouvrirRefus(int $id): void
    {
        $this->refusId = $id;
        $this->motif = '';
    }

    public function confirmerRefus(): void
    {
        $r = Api::post("/admin/documents/{$this->refusId}/decision", ['decision' => 'refuser', 'motif' => trim($this->motif)], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Une erreur est survenue.';

            return;
        }
        $this->refusId = null;
        $this->erreur = null;
        $this->message = 'Proposition refusée : le membre est prévenu du motif.';
        AdminNav::oublier();
    }

    // ── Catégories ───────────────────────────────────────────────────────

    public function ajouterCategorie(): void
    {
        $r = Api::post('/admin/document-categories', ['nom' => trim($this->nouvelleCategorie)], Api::token());
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Une erreur est survenue.');
        if ($r['ok'] ?? false) {
            $this->nouvelleCategorie = '';
            $this->message = 'Catégorie ajoutée.';
        }
    }

    public function editerCategorie(int $id, string $nom): void
    {
        $this->categorieEditee = $id;
        $this->nomCategorie = $nom;
    }

    public function renommerCategorie(): void
    {
        $r = Api::put("/admin/document-categories/{$this->categorieEditee}", ['nom' => trim($this->nomCategorie)], Api::token());
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Une erreur est survenue.');
        if ($r['ok'] ?? false) {
            $this->categorieEditee = null;
            $this->message = 'Catégorie renommée.';
        }
    }

    public function supprimerCategorie(int $id): void
    {
        $r = Api::delete("/admin/document-categories/{$id}", Api::token());
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Une erreur est survenue.');
        $this->message = ($r['ok'] ?? false) ? 'Catégorie supprimée.' : null;
    }

    public function render()
    {
        $data = Api::get('/admin/documents', array_filter(['statut' => $this->filtre, 'q' => trim($this->recherche)]), Api::token());
        $compteurs = $data['compteurs'] ?? [];

        return view('livewire.admin.documents', [
            'docs' => Collection::make($data['documents'] ?? []),
            'categories' => Collection::make($data['categories'] ?? []),
            'groupes' => $data['groupes'] ?? [],
            'listeAcces' => $data['acces'] ?? [],
            'compteurs' => $compteurs + ['' => array_sum($compteurs)],
            'stats' => $data['stats'] ?? [],
        ]);
    }
}
