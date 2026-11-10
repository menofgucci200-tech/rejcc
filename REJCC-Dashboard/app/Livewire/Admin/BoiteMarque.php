<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Boîte à outils de marque de l'équipe Communication : repères clés de la
 * charte (couleurs, polices, slogan, tailles du logo) et documents de marque
 * à téléverser et télécharger (identité, charte, logos, gabarits…). Les
 * fichiers sont stockés en privé (disque partagé, dossier marque/) et servis
 * par MarqueFichierController après contrôle de la rubrique.
 *
 * Choix : une rubrique distincte de « Documents », qui est la bibliothèque
 * des membres (accès « tous les membres » par défaut, notifications,
 * propositions) : les fichiers de travail de la Com n'ont pas à y figurer.
 */
#[Layout('layouts.admin-light')]
class BoiteMarque extends Component
{
    use WithFileUploads;

    /** Formats acceptés : documents, images, fichiers sources légers, polices, archives. */
    public const EXTENSIONS = 'pdf,png,jpg,jpeg,webp,gif,svg,ai,eps,psd,indd,doc,docx,ppt,pptx,key,xls,xlsx,odt,odp,txt,ttf,otf,woff,woff2,zip,mp4,mov';

    /** 30 Mo : sous la limite de 32 Mo des envois en production. */
    public const TAILLE_MAX_KO = 30720;

    #[Url(except: '')]
    public string $categorie = '';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    public ?string $message = null;

    public ?string $erreur = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $titre = '';

    public string $description = '';

    public string $categorieForm = 'identite';

    public string $lien = '';

    public $fichierUpload = null;

    /** ['chemin', 'nom', 'mime', 'octets', 'existant'] : verrouillé, le navigateur ne peut pas le modifier. */
    #[Locked]
    public ?array $fichier = null;

    public function setCategorie(string $c): void
    {
        $this->categorie = $c;
    }

    public function openCreate(): void
    {
        $this->abandonnerEnvoi();
        $this->reset(['editingId', 'titre', 'description', 'lien', 'fichierUpload', 'fichier', 'erreur', 'message']);
        $this->categorieForm = $this->categorie ?: 'identite';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->abandonnerEnvoi();
        $f = collect(Api::get('/admin/communication/fichiers', [], Api::token())['fichiers'] ?? [])->firstWhere('id', $id);
        if (! $f || ! $f['modifiable']) {
            return;
        }
        $this->editingId = $id;
        $this->titre = $f['titre'];
        $this->description = (string) $f['description'];
        $this->categorieForm = $f['categorie'];
        $this->lien = (string) $f['lien'];
        $this->fichier = $f['a_fichier'] ? ['chemin' => null, 'nom' => $f['fichier_nom'], 'mime' => $f['mime'], 'octets' => $f['octets'], 'existant' => true] : null;
        $this->fichierUpload = null;
        $this->erreur = $this->message = null;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->abandonnerEnvoi();
        $this->showForm = false;
        $this->editingId = null;
        $this->fichier = null;
    }

    /** Fichier envoyé mais jamais enregistré : on le retire du stockage. */
    private function abandonnerEnvoi(): void
    {
        if ($this->fichier && ! ($this->fichier['existant'] ?? false) && self::cheminMarque($this->fichier['chemin'] ?? null)) {
            Storage::disk('local')->delete($this->fichier['chemin']);
        }
    }

    /** Seuls les fichiers du dossier marque/ peuvent être supprimés d'ici. */
    private static function cheminMarque(?string $chemin): bool
    {
        return is_string($chemin) && str_starts_with($chemin, 'marque/') && ! str_contains($chemin, '..');
    }

    public function updatedFichierUpload(): void
    {
        $this->validate(['fichierUpload' => 'file|max:'.self::TAILLE_MAX_KO.'|extensions:'.self::EXTENSIONS], [
            'fichierUpload.max' => 'Le fichier ne doit pas dépasser 30 Mo.',
            'fichierUpload.extensions' => 'Format non pris en charge (PDF, image, SVG, AI, EPS, PSD, Word, PowerPoint, Excel, police, ZIP).',
            'fichierUpload.uploaded' => "L'envoi a échoué : le fichier est peut-être trop lourd.",
        ]);
        $this->abandonnerEnvoi();
        $nom = $this->fichierUpload->getClientOriginalName();
        $ext = strtolower($this->fichierUpload->getClientOriginalExtension());
        $base = Str::slug(pathinfo($nom, PATHINFO_FILENAME)) ?: 'document';
        $chemin = $this->fichierUpload->storeAs('marque/'.date('Y/m'), Str::limit($base, 80, '').'-'.Str::lower(Str::random(6)).'.'.$ext, 'local');
        $this->fichier = ['chemin' => $chemin, 'nom' => $nom, 'mime' => $this->fichierUpload->getMimeType(), 'octets' => $this->fichierUpload->getSize(), 'existant' => false];
        if ($this->titre === '') {
            $this->titre = Str::limit(trim(preg_replace('/[_-]+/', ' ', pathinfo($nom, PATHINFO_FILENAME))), 150, '');
        }
        $this->fichierUpload = null;
        $this->lien = '';
    }

    public function retirerFichier(): void
    {
        $this->abandonnerEnvoi();
        $this->fichier = null;
    }

    public function save(): void
    {
        $data = ['titre' => trim($this->titre), 'description' => trim($this->description) ?: null, 'categorie' => $this->categorieForm];
        if ($this->fichier && ($this->fichier['existant'] ?? false)) {
            // Fichier inchangé : on renvoie ses informations telles quelles.
            $actuel = collect(Api::get('/admin/communication/fichiers', [], Api::token())['fichiers'] ?? [])->firstWhere('id', $this->editingId);
            $acces = Api::get("/admin/communication/fichiers/{$this->editingId}/acces", [], Api::token());
            $data += ['fichier' => $acces['fichier']['chemin'] ?? null, 'fichier_nom' => $actuel['fichier_nom'] ?? null, 'mime' => $actuel['mime'] ?? null, 'octets' => $actuel['octets'] ?? null];
        } elseif ($this->fichier) {
            $data += ['fichier' => $this->fichier['chemin'], 'fichier_nom' => $this->fichier['nom'], 'mime' => $this->fichier['mime'], 'octets' => $this->fichier['octets']];
        } else {
            $data['lien'] = trim($this->lien) ?: null;
        }

        $r = $this->editingId
            ? Api::put("/admin/communication/fichiers/{$this->editingId}", $data, Api::token())
            : Api::post('/admin/communication/fichiers', $data, Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? "L'enregistrement a échoué.";

            return;
        }
        if (self::cheminMarque($r['ancien_fichier'] ?? null)) {
            Storage::disk('local')->delete($r['ancien_fichier']);
        }
        $this->message = $this->editingId ? 'Document mis à jour.' : 'Document ajouté à la boîte à outils.';
        $this->erreur = null;
        $this->fichier = null; // enregistré : ne plus le supprimer à la fermeture
        $this->closeForm();
    }

    public function delete(int $id): void
    {
        $r = Api::delete("/admin/communication/fichiers/{$id}", Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Suppression impossible.';

            return;
        }
        if (self::cheminMarque($r['fichier'] ?? null)) {
            Storage::disk('local')->delete($r['fichier']);
        }
        $this->message = 'Document supprimé.';
        $this->erreur = null;
    }

    public function render()
    {
        $data = Api::get('/admin/communication/fichiers', array_filter(['categorie' => $this->categorie, 'q' => trim($this->recherche)]), Api::token());
        $compteurs = $data['compteurs'] ?? [];

        return view('livewire.admin.boite-marque', [
            'fichiers' => collect($data['fichiers'] ?? []),
            'categories' => $data['categories'] ?? [],
            'compteurs' => $compteurs + ['' => array_sum($compteurs)],
            'logos' => [
                ['Logo principal, couleur', 'rejcc-logo-color', 'bg-white'],
                ['Logo principal, blanc', 'rejcc-logo-white', 'bg-brand'],
                ['Monogramme, couleur', 'rejcc-monogram-color', 'bg-white'],
                ['Monogramme, blanc', 'rejcc-monogram-white', 'bg-brand'],
            ],
        ]);
    }
}
