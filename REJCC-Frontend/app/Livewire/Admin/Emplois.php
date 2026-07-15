<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Emplois extends Component
{
    use HandlesMedia;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $type = 'emploi';

    public string $entreprise = '';

    public string $site_url = '';

    public string $lieu = '';

    public string $description = '';

    public string $contact = '';

    public string $deadline = '';

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:4|max:160',
            'type' => 'required|string|max:40',
            'entreprise' => 'nullable|string|max:160',
            'site_url' => 'nullable|url|max:500',
            'lieu' => 'nullable|string|max:160',
            'description' => 'required|string|min:20|max:3000',
            'contact' => 'nullable|string|max:160',
            'deadline' => 'nullable|date',
        ];
    }

    protected function annonces(): Collection
    {
        return Collection::make(Api::get('/admin/opportunities', [], Api::token())['opportunities'] ?? []);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'entreprise', 'site_url', 'lieu', 'description', 'contact', 'deadline']);
        $this->clearMedia();
        $this->type = 'emploi';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $o = $this->annonces()->firstWhere('id', $id);
        if (! $o) {
            return;
        }

        $this->editingId = $o['id'];
        $this->title = $o['title'];
        $this->type = $o['type'];
        $this->entreprise = $o['entreprise'] ?? '';
        $this->site_url = $o['site_url'] ?? '';
        $this->lieu = $o['lieu'] ?? '';
        $this->description = $o['description'];
        $this->contact = $o['contact'] ?? '';
        $this->deadline = $o['deadline'] ?? '';
        $this->fillMedia($o['media_url'] ?? null, $o['media_name'] ?? null);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'type' => $this->type,
            'entreprise' => $this->entreprise ?: null,
            'site_url' => $this->site_url ?: null,
            'lieu' => $this->lieu ?: null,
            'description' => $this->description,
            'contact' => $this->contact ?: null,
            'deadline' => $this->deadline ?: null,
            'media_url' => $this->mediaUrl ?: null,
            'media_name' => $this->mediaName ?: null,
        ];
        $token = Api::token();

        if ($this->editingId) {
            Api::put("/admin/opportunities/{$this->editingId}", $data, $token);
        } else {
            // L'admin publie via le même endpoint que les membres (auteur = admin).
            Api::post('/opportunities', array_filter($data), $token);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/opportunities/{$id}", Api::token());
    }

    public function render()
    {
        $annonces = $this->annonces()->map(fn (array $o) => [
            'id' => $o['id'],
            'titre' => $o['title'],
            'type' => strtolower($o['type']),
            'entreprise' => $o['entreprise'] ?? null,
            'lieu' => $o['lieu'] ?? null,
            'description' => $o['description'],
            'auteur' => $o['author'] ?? 'REJCC',
            'contact' => $o['contact'],
            'deadline' => $o['deadline'] ? Carbon::parse($o['deadline'])->translatedFormat('j F Y') : null,
            'date' => Carbon::parse($o['created_at'])->diffForHumans(),
        ]);

        return view('livewire.admin.emplois', ['annonces' => $annonces]);
    }
}
