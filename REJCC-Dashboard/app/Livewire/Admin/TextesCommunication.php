<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Textes prêts à copier de la Communication : présentations du REJCC,
 * signature d'e-mail, bios des réseaux, hashtags, messages types par type
 * d'information et mentions de consentement. Bouton « Copier » et
 * compteur de caractères (avec la limite conseillée, pour les bios).
 */
#[Layout('layouts.admin-light')]
class TextesCommunication extends Component
{
    #[Url(except: '')]
    public string $categorie = '';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    public ?string $message = null;

    public ?string $erreur = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $categorieForm = 'messages';

    public string $titre = '';

    public string $contenu = '';

    public string $limite = '';

    public function setCategorie(string $c): void
    {
        $this->categorie = $c;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'titre', 'contenu', 'limite', 'erreur', 'message']);
        $this->categorieForm = $this->categorie ?: 'messages';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $t = collect(Api::get('/admin/communication/textes', [], Api::token())['textes'] ?? [])->firstWhere('id', $id);
        if (! $t || ! $t['modifiable']) {
            return;
        }
        $this->editingId = $id;
        $this->categorieForm = $t['categorie'];
        $this->titre = $t['titre'];
        $this->contenu = $t['contenu'];
        $this->limite = (string) ($t['limite'] ?? '');
        $this->erreur = $this->message = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        $data = ['categorie' => $this->categorieForm, 'titre' => trim($this->titre), 'contenu' => $this->contenu, 'limite' => $this->limite !== '' ? (int) $this->limite : null];
        $r = $this->editingId
            ? Api::put("/admin/communication/textes/{$this->editingId}", $data, Api::token())
            : Api::post('/admin/communication/textes', $data, Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? "L'enregistrement a échoué.";

            return;
        }
        $this->message = $this->editingId ? 'Texte mis à jour.' : 'Texte ajouté à la bibliothèque.';
        $this->erreur = null;
        $this->closeForm();
    }

    public function delete(int $id): void
    {
        $r = Api::delete("/admin/communication/textes/{$id}", Api::token());
        $this->message = ($r['ok'] ?? false) ? 'Texte supprimé.' : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Suppression impossible.');
    }

    public function restaurer(): void
    {
        $r = Api::post('/admin/communication/textes/restaurer', [], Api::token());
        $n = (int) ($r['ajoutes'] ?? 0);
        $this->message = ($r['ok'] ?? false) ? ($n ? "{$n} texte".($n > 1 ? 's' : '').' par défaut restauré'.($n > 1 ? 's' : '').'.' : 'Tous les textes par défaut sont déjà là.') : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Action impossible.');
    }

    public function render()
    {
        $data = Api::get('/admin/communication/textes', array_filter(['categorie' => $this->categorie, 'q' => trim($this->recherche)]), Api::token());
        $compteurs = $data['compteurs'] ?? [];

        return view('livewire.admin.textes-communication', [
            'textes' => collect($data['textes'] ?? [])->groupBy('categorie'),
            'categories' => $data['categories'] ?? [],
            'compteurs' => $compteurs + ['' => array_sum($compteurs)],
            'defautsManquants' => (int) ($data['defauts_manquants'] ?? 0),
        ]);
    }
}
