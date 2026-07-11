<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Contenu extends Component
{
    /** Onglets : type côté API => libellé. */
    public const ONGLETS = [
        'sectors' => 'Secteurs',
        'testimonials' => 'Témoignages',
        'partners' => 'Partenaires',
        'stats' => 'Chiffres clés',
        'steps' => "Étapes d'adhésion",
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
        $this->reset(['editingId', 'title', 'blurb', 'items', 'icon', 'name', 'role', 'quote', 'sector', 'label', 'value', 'suffix', 'text']);
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
                    'sector' => 'required|string|min:2|max:120',
                ]);
                $data = ['name' => $this->name, 'sector' => $this->sector];
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
            'items' => $this->contenu(),
        ]);
    }
}
