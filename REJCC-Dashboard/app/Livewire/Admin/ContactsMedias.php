<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Fichier des contacts médias du département Communication : journalistes,
 * radios, télévisions, médias web, influenceurs, institutions. Recherche,
 * filtre par type, export CSV. Données personnelles : rubrique
 * « communication » seulement.
 */
#[Layout('layouts.admin-light')]
class ContactsMedias extends Component
{
    #[Url(except: '')]
    public string $type = '';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    public ?string $message = null;

    public ?string $erreur = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $ouvert = null;

    /** Champs du formulaire. */
    public array $form = [];

    private const VIDE = ['nom' => '', 'media' => '', 'type' => 'presse', 'fonction' => '', 'email' => '', 'telephone' => '', 'ville' => '', 'notes' => '', 'dernier_contact' => ''];

    public function mount(): void
    {
        $this->form = self::VIDE;
    }

    public function setType(string $t): void
    {
        $this->type = $t;
    }

    public function basculer(int $id): void
    {
        $this->ouvert = $this->ouvert === $id ? null : $id;
    }

    public function openCreate(): void
    {
        $this->form = self::VIDE;
        if ($this->type !== '') {
            $this->form['type'] = $this->type;
        }
        $this->editingId = null;
        $this->erreur = $this->message = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $c = collect(Api::get('/admin/communication/contacts-medias', [], Api::token())['contacts'] ?? [])->firstWhere('id', $id);
        if (! $c) {
            return;
        }
        $this->form = array_map(fn ($v) => (string) $v, array_intersect_key($c, self::VIDE));
        $this->editingId = $id;
        $this->erreur = $this->message = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    /** Raccourci : « contacté aujourd'hui ». */
    public function contacteAujourdhui(int $id): void
    {
        $c = collect(Api::get('/admin/communication/contacts-medias', [], Api::token())['contacts'] ?? [])->firstWhere('id', $id);
        if (! $c) {
            return;
        }
        $r = Api::put("/admin/communication/contacts-medias/{$id}", array_merge(array_intersect_key($c, self::VIDE), ['dernier_contact' => now()->toDateString()]), Api::token());
        $this->message = ($r['ok'] ?? false) ? "Dernier contact avec {$c['nom']} : aujourd'hui." : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Action impossible.');
    }

    public function save(): void
    {
        $data = array_map(fn ($v) => trim((string) $v), array_intersect_key($this->form, self::VIDE));
        $r = $this->editingId
            ? Api::put("/admin/communication/contacts-medias/{$this->editingId}", $data, Api::token())
            : Api::post('/admin/communication/contacts-medias', $data, Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? "L'enregistrement a échoué.";

            return;
        }
        $this->message = $this->editingId ? 'Contact mis à jour.' : 'Contact ajouté.';
        $this->erreur = null;
        $this->closeForm();
    }

    public function delete(int $id): void
    {
        $r = Api::delete("/admin/communication/contacts-medias/{$id}", Api::token());
        $this->message = ($r['ok'] ?? false) ? 'Contact supprimé.' : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Suppression impossible.');
    }

    public function render()
    {
        $data = Api::get('/admin/communication/contacts-medias', array_filter(['type' => $this->type, 'q' => trim($this->recherche)]), Api::token());
        $compteurs = $data['compteurs'] ?? [];

        return view('livewire.admin.contacts-medias', [
            'contacts' => collect($data['contacts'] ?? []),
            'types' => $data['types'] ?? [],
            'compteurs' => $compteurs + ['' => array_sum($compteurs)],
        ]);
    }
}
