<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Documents extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $category = '';

    public string $url = '';

    public string $size = '';

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
            'category' => 'required|string|max:100',
            'url' => 'required|url|max:500',
            'size' => 'nullable|string|max:20',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'description', 'category', 'url', 'size']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $docs = Collection::make(Api::get('/admin/documents', [], Api::token())['documents'] ?? []);
        $doc = $docs->firstWhere('id', $id);

        if (! $doc) {
            return;
        }

        $this->editingId = $doc['id'];
        $this->title = $doc['title'];
        $this->description = $doc['description'] ?? '';
        $this->category = $doc['category'];
        $this->url = $doc['url'];
        $this->size = $doc['size'] ?? '';
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
        $data = $this->validate();
        $token = Api::token();

        if ($this->editingId) {
            Api::put("/admin/documents/{$this->editingId}", $data, $token);
        } else {
            Api::post('/admin/documents', $data, $token);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/documents/{$id}", Api::token());
    }

    public function render()
    {
        $docs = Collection::make(Api::get('/admin/documents', [], Api::token())['documents'] ?? [])
            ->map(fn ($d) => (object) $d);

        return view('livewire.admin.documents', [
            'docs' => $docs,
            'categories' => $docs->pluck('category')->unique(),
        ]);
    }
}
