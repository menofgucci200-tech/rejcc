<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Parcours extends Component
{
    public const BADGE_ICONS = ['rocket', 'award', 'sparkles', 'star', 'flame', 'gem', 'shield-check', 'graduation-cap'];

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $objectif = '';

    public string $badgeIcon = 'rocket';

    public string $badgeCouleur = '#4F6FBF';

    public int $ordre = 0;

    public bool $isPublished = true;

    // ── Formations attachées ─────────────────────────────────────────────
    public ?int $formationsPathId = null;

    /** @var array<int> Identifiants de formations, dans l'ordre du parcours. */
    public array $formationIds = [];

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:150',
            'description' => 'nullable|string|max:2000',
            'objectif' => 'nullable|string|max:300',
            'badgeIcon' => 'required|string|max:40',
            'badgeCouleur' => 'nullable|string|max:20',
            'ordre' => 'integer|min:0|max:1000',
            'isPublished' => 'boolean',
        ];
    }

    protected function paths(): Collection
    {
        return Collection::make(Api::get('/admin/paths', [], Api::token())['paths'] ?? []);
    }

    protected function toutesFormations(): Collection
    {
        return Collection::make(Api::get('/admin/formations', [], Api::token())['formations'] ?? []);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'description', 'objectif']);
        $this->badgeIcon = 'rocket';
        $this->badgeCouleur = '#4F6FBF';
        $this->ordre = 0;
        $this->isPublished = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $p = $this->paths()->firstWhere('id', $id);
        if (! $p) {
            return;
        }

        $this->editingId = $p['id'];
        $this->title = $p['title'];
        $this->description = $p['description'] ?? '';
        $this->objectif = $p['objectif'] ?? '';
        $this->badgeIcon = $p['badge_icon'] ?: 'rocket';
        $this->badgeCouleur = $p['badge_couleur'] ?: '#4F6FBF';
        $this->ordre = (int) $p['ordre'];
        $this->isPublished = (bool) $p['is_published'];
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
            'description' => $this->description ?: null,
            'objectif' => $this->objectif ?: null,
            'badge_icon' => $this->badgeIcon,
            'badge_couleur' => $this->badgeCouleur ?: null,
            'ordre' => $this->ordre,
            'is_published' => $this->isPublished,
        ];
        $token = Api::token();

        if ($this->editingId) {
            Api::put("/admin/paths/{$this->editingId}", $data, $token);
        } else {
            Api::post('/admin/paths', $data, $token);
        }

        $this->closeForm();
    }

    public function togglePublication(int $id): void
    {
        $p = $this->paths()->firstWhere('id', $id);
        if (! $p) {
            return;
        }

        Api::put("/admin/paths/{$id}", [
            'title' => $p['title'],
            'is_published' => ! $p['is_published'],
        ], Api::token());
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/paths/{$id}", Api::token());
    }

    // ── Formations attachées ─────────────────────────────────────────────

    public function toggleFormations(int $pathId): void
    {
        if ($this->formationsPathId === $pathId) {
            $this->formationsPathId = null;
            $this->formationIds = [];

            return;
        }

        $this->formationsPathId = $pathId;
        $detail = Api::get("/admin/paths/{$pathId}", [], Api::token())['path'] ?? [];
        $this->formationIds = collect($detail['formations'] ?? [])->pluck('id')->all();
    }

    public function ajouterFormation(int $formationId): void
    {
        if (! in_array($formationId, $this->formationIds, true)) {
            $this->formationIds[] = $formationId;
        }
    }

    public function retirerFormation(int $formationId): void
    {
        $this->formationIds = array_values(array_filter($this->formationIds, fn ($id) => $id !== $formationId));
    }

    public function monter(int $index): void
    {
        if ($index <= 0) {
            return;
        }
        [$this->formationIds[$index - 1], $this->formationIds[$index]] = [$this->formationIds[$index], $this->formationIds[$index - 1]];
    }

    public function descendre(int $index): void
    {
        if ($index >= count($this->formationIds) - 1) {
            return;
        }
        [$this->formationIds[$index + 1], $this->formationIds[$index]] = [$this->formationIds[$index], $this->formationIds[$index + 1]];
    }

    public function enregistrerFormations(): void
    {
        if (! $this->formationsPathId || empty($this->formationIds)) {
            return;
        }

        Api::put("/admin/paths/{$this->formationsPathId}/formations", [
            'formation_ids' => $this->formationIds,
        ], Api::token());
    }

    public function render()
    {
        $formations = $this->toutesFormations();

        return view('livewire.admin.parcours', [
            'paths' => $this->paths(),
            'toutesFormations' => $formations,
            'formationsChoisies' => collect($this->formationIds)
                ->map(fn ($id) => $formations->firstWhere('id', $id))
                ->filter()
                ->values(),
            'formationsDisponibles' => $formations->whereNotIn('id', $this->formationIds)->values(),
        ]);
    }
}
