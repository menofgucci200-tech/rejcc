<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use App\Support\ProjectStatus;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Projets extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public int $membersCount = 1;

    public string $status = 'En évaluation';

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:4|max:160',
            'description' => 'required|string|min:20|max:3000',
            'membersCount' => 'required|integer|min:1|max:500',
            'status' => 'required|string|max:60',
        ];
    }

    protected function projets(): Collection
    {
        return Collection::make(Api::get('/admin/projects', [], Api::token())['projects'] ?? []);
    }

    public function openEdit(int $id): void
    {
        $p = $this->projets()->firstWhere('id', $id);
        if (! $p) {
            return;
        }

        $this->editingId = $p['id'];
        $this->title = $p['title'];
        $this->description = $p['description'];
        $this->membersCount = (int) $p['members_count'];
        $this->status = $p['status'];
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

        Api::put("/admin/projects/{$this->editingId}", [
            'title' => $this->title,
            'description' => $this->description,
            'members_count' => $this->membersCount,
            'status' => $this->status,
        ], Api::token());

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/projects/{$id}", Api::token());
    }

    public function render()
    {
        $projets = $this->projets()->map(fn (array $p) => [
            'id' => $p['id'],
            'titre' => $p['title'],
            'description' => $p['description'],
            'membres' => (int) $p['members_count'],
            'statut' => $p['status'],
            'statutColor' => ProjectStatus::color($p['status']),
            'porteur' => $p['porteur'] ?? 'REJCC',
        ]);

        return view('livewire.admin.projets', [
            'projets' => $projets,
            'statutOptions' => ProjectStatus::OPTIONS,
        ]);
    }
}
