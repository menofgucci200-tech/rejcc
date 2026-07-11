<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Actualites extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $category = '';

    public string $excerpt = '';

    public string $body = '';

    public string $author = '';

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:180',
            'category' => 'required|string|min:2|max:60',
            'excerpt' => 'required|string|min:10|max:300',
            'body' => 'required|string|min:20|max:20000',
            'author' => 'nullable|string|max:120',
        ];
    }

    protected function articles(): Collection
    {
        return Collection::make(Api::get('/admin/news', [], Api::token())['articles'] ?? []);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'category', 'excerpt', 'body', 'author']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $a = $this->articles()->firstWhere('id', $id);
        if (! $a) {
            return;
        }

        $this->editingId = $a['id'];
        $this->title = $a['title'];
        $this->category = $a['category'];
        $this->excerpt = $a['excerpt'];
        $this->body = implode("\n\n", $a['body'] ?? []);
        $this->author = $a['author'] ?? '';
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
            'category' => $this->category,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'author' => $this->author ?: null,
        ];
        $token = Api::token();

        if ($this->editingId) {
            Api::put("/admin/news/{$this->editingId}", $data, $token);
        } else {
            Api::post('/admin/news', $data, $token);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/news/{$id}", Api::token());
    }

    public function render()
    {
        $articles = $this->articles()->map(fn (array $a) => [
            'id' => $a['id'],
            'titre' => $a['title'],
            'categorie' => $a['category'],
            'extrait' => $a['excerpt'],
            'slug' => $a['slug'],
            'lecture' => $a['reading_time'],
            'publie' => Carbon::parse($a['published_at'])->translatedFormat('j F Y'),
        ]);

        return view('livewire.admin.actualites', ['articles' => $articles]);
    }
}
