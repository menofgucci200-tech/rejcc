<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Contenu extends Component
{
    use HandlesMedia;

    /** Onglets : type côté API => libellé. */
    public const ONGLETS = [
        'sectors' => 'Secteurs',
        'testimonials' => 'Témoignages',
        'partners' => 'Partenaires',
        'stats' => 'Chiffres clés',
        'steps' => "Étapes d'adhésion",
        'gallery' => 'Galerie photos',
    ];

    public string $onglet = 'sectors';

    public bool $showForm = false;

    public ?int $editingId = null;

    // Champs (superset de tous les types ; chaque onglet utilise les siens).
    public string $title = '';

    public string $blurb = '';

    public string $items = ''; // un élément par ligne

    public string $icon = '';

    public string $name = '';

    public string $role = '';

    public string $quote = '';

    public string $sector = '';

    public string $label = '';

    public ?int $value = null;

    public string $suffix = '';

    public string $text = '';

    public string $caption = '';

    public string $site_url = '';

    public function setOnglet(string $onglet): void
    {
        if (isset(self::ONGLETS[$onglet])) {
            $this->onglet = $onglet;
            $this->closeForm();
        }
    }

    protected function contenu(): Collection
    {
        return Collection::make(Api::get("/admin/site-content/{$this->onglet}", [], Api::token())['items'] ?? []);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'blurb', 'items', 'icon', 'name', 'role', 'quote', 'sector', 'label', 'value', 'suffix', 'text', 'caption', 'site_url']);
        $this->clearMedia();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $item = $this->contenu()->firstWhere('id', $id);
        if (! $item) {
            return;
        }

        $this->openCreate();
        $this->editingId = $id;
        $this->title = $item['title'] ?? '';
        $this->blurb = $item['blurb'] ?? '';
        $this->items = implode("\n", $item['items'] ?? []);
        $this->icon = $item['icon'] ?? '';
        $this->name = $item['name'] ?? '';
        $this->role = $item['role'] ?? '';
        $this->quote = $item['quote'] ?? '';
        $this->sector = $item['sector'] ?? '';
        $this->label = $item['label'] ?? '';
        $this->value = $item['value'] ?? null;
        $this->suffix = $item['suffix'] ?? '';
        $this->text = $item['text'] ?? '';
        $this->caption = $item['caption'] ?? '';
        $this->site_url = $item['site_url'] ?? '';
        if ($this->onglet === 'gallery') {
            $this->fillMedia($item['url'] ?? null);
        }
        if ($this->onglet === 'partners') {
            $this->fillMedia($item['logo'] ?? null);
        }
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        switch ($this->onglet) {
            case 'sectors':
                $this->validate([
                    'icon' => 'required|string|max:40',
                    'title' => 'required|string|min:2|max:120',
                    'blurb' => 'required|string|min:5|max:300',
                    'items' => 'required|string|min:2',
                ]);
                $data = [
                    'icon' => $this->icon,
                    'title' => $this->title,
                    'blurb' => $this->blurb,
                    'items' => array_values(array_filter(array_map('trim', explode("\n", $this->items)))),
                ];
                break;

            case 'testimonials':
                $this->validate([
                    'name' => 'required|string|min:2|max:120',
                    'role' => 'required|string|min:2|max:120',
                    'quote' => 'required|string|min:10|max:600',
                ]);
                $data = ['name' => $this->name, 'role' => $this->role, 'quote' => $this->quote];
                break;

            case 'partners':
                $this->validate([
                    'name' => 'required|string|min:2|max:120',
                    'sector' => 'nullable|string|max:120',
                    'site_url' => 'nullable|url|max:500',
                ], [
                    'name.required' => "Indiquez le nom de l'entreprise / organisation.",
                    'site_url.url' => 'Le lien du site doit être une adresse valide (https://…).',
                ]);
                // Logo et site web optionnels : sans logo la vitrine affiche le
                // nom, sans site web le logo n'est pas cliquable.
                $data = [
                    'name' => $this->name,
                    'sector' => $this->sector ?: null,
                    'logo' => $this->mediaUrl ?: null,
                    'site_url' => $this->site_url ?: null,
                ];
                break;

            case 'stats':
                $this->validate([
                    'label' => 'required|string|min:2|max:120',
                    'value' => 'required|integer|min:0',
                    'suffix' => 'nullable|string|max:10',
                ]);
                $data = ['label' => $this->label, 'value' => $this->value, 'suffix' => $this->suffix ?: null];
                break;

            case 'steps':
                $this->validate([
                    'icon' => 'required|string|max:40',
                    'title' => 'required|string|min:2|max:160',
                    'text' => 'required|string|min:5|max:400',
                ]);
                $data = ['icon' => $this->icon, 'title' => $this->title, 'text' => $this->text];
                break;

            case 'gallery':
                if (! $this->mediaUrl) {
                    $this->addError('mediaFile', 'Ajoutez une photo (fichier ou lien).');

                    return;
                }
                $this->validate(['caption' => 'nullable|string|max:200']);
                $data = ['url' => $this->mediaUrl, 'caption' => $this->caption ?: null];
                break;

            default:
                return;
        }

        $token = Api::token();
        if ($this->editingId) {
            Api::put("/admin/site-content/{$this->onglet}/{$this->editingId}", $data, $token);
        } else {
            Api::post("/admin/site-content/{$this->onglet}", $data, $token);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/site-content/{$this->onglet}/{$id}", Api::token());
    }

    public function render()
    {
        return view('livewire.admin.contenu', [
            'onglets' => self::ONGLETS,
            // « elements » et non « items » : la propriété publique $items
            // (textarea des filières) écraserait la variable de vue.
            'elements' => $this->contenu(),
        ]);
    }
}
