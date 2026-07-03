<?php

namespace App\Livewire\Admin;

use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
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
        $doc = Document::findOrFail($id);
        $this->editingId = $doc->id;
        $this->title = $doc->title;
        $this->description = $doc->description ?? '';
        $this->category = $doc->category;
        $this->url = $doc->url;
        $this->size = $doc->size ?? '';
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

        if ($this->editingId) {
            Document::findOrFail($this->editingId)->update($data);
        } else {
            Document::create($data);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Document::findOrFail($id)->delete();
    }

    public function render()
    {
        $docs = Document::orderBy('category')->orderBy('title')->get();

        return view('livewire.admin.documents', [
            'docs' => $docs,
            'categories' => $docs->pluck('category')->unique(),
        ]);
    }
}
