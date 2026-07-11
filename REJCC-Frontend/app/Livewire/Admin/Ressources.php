<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Ressources extends Component
{
    private const EMOJIS = [
        'Ebook' => ['emoji' => '📘', 'tint' => '#E8EDF8'],
        'Modèle' => ['emoji' => '📊', 'tint' => '#F9E9E9'],
        'Vidéo' => ['emoji' => '🎥', 'tint' => '#EAF6EE'],
        'Audio' => ['emoji' => '🎙️', 'tint' => '#FDF3E3'],
        'Document' => ['emoji' => '📄', 'tint' => '#E8EDF8'],
    ];

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $type = 'Document';

    public string $description = '';

    public string $url = '';

    public string $size = '';

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:200',
            'type' => 'required|in:Ebook,Modèle,Vidéo,Audio,Document',
            'description' => 'nullable|string|max:1000',
            'url' => 'required|url|max:500',
            'size' => 'nullable|string|max:20',
        ];
    }

    protected function ressources(): Collection
    {
        return Collection::make(Api::get('/admin/resources', [], Api::token())['resources'] ?? []);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'description', 'url', 'size']);
        $this->type = 'Document';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $r = $this->ressources()->firstWhere('id', $id);
        if (! $r) {
            return;
        }

        $this->editingId = $r['id'];
        $this->title = $r['title'];
        $this->type = $r['type'];
        $this->description = $r['description'] ?? '';
        $this->url = $r['url'];
        $this->size = $r['size'] ?? '';
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
            'description' => $this->description ?: null,
            'url' => $this->url,
            'size' => $this->size ?: null,
        ];
        $token = Api::token();

        if ($this->editingId) {
            Api::put("/admin/resources/{$this->editingId}", $data, $token);
        } else {
            Api::post('/admin/resources', $data, $token);
        }

        $this->closeForm();
    }

    public function toggleVisibilite(int $id): void
    {
        $r = $this->ressources()->firstWhere('id', $id);
        if (! $r) {
            return;
        }

        Api::put("/admin/resources/{$id}", [
            'title' => $r['title'],
            'type' => $r['type'],
            'url' => $r['url'],
            'is_published' => ! $r['is_published'],
        ], Api::token());
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/resources/{$id}", Api::token());
    }

    public function render()
    {
        $ressources = $this->ressources()->map(function (array $r) {
            $style = self::EMOJIS[$r['type']] ?? self::EMOJIS['Document'];

            return [
                'id' => $r['id'],
                'titre' => $r['title'],
                'type' => $r['type'],
                'taille' => $r['size'] ?? '—',
                'telechargements' => (int) $r['downloads'],
                'visible' => (bool) $r['is_published'],
                'emoji' => $style['emoji'],
                'tint' => $style['tint'],
            ];
        });

        return view('livewire.admin.ressources', ['ressources' => $ressources]);
    }
}
