<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\Api;
use App\Support\CategoryPalette;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Formations extends Component
{
    use HandlesMedia;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $category = '';

    public string $description = '';

    public string $duration = '';

    public string $level = '';

    public int $modulesCount = 1;

    public bool $isFree = true;

    public bool $isCertifying = false;

    // ── Modules d'une formation ──────────────────────────────────────────
    public ?int $modulesFormationId = null;

    public ?int $moduleEditingId = null;

    public string $moduleTitre = '';

    public string $moduleDescription = '';

    public string $moduleVideoUrl = '';

    public string $moduleDocumentUrl = '';

    public string $moduleDuree = '';

    public int $moduleOrdre = 0;

    public bool $moduleFormOpen = false;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:200',
            'category' => 'required|string|min:2|max:100',
            'description' => 'nullable|string|max:2000',
            'duration' => 'nullable|string|max:50',
            'level' => 'nullable|string|max:50',
            'modulesCount' => 'required|integer|min:1|max:50',
            'isFree' => 'boolean',
            'isCertifying' => 'boolean',
        ];
    }

    protected function formations(): Collection
    {
        return Collection::make(Api::get('/admin/formations', [], Api::token())['formations'] ?? []);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'category', 'description', 'duration', 'level']);
        $this->modulesCount = 1;
        $this->isFree = true;
        $this->isCertifying = false;
        $this->clearMedia();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $f = $this->formations()->firstWhere('id', $id);
        if (! $f) {
            return;
        }

        $this->editingId = $f['id'];
        $this->title = $f['title'];
        $this->category = $f['category'];
        $this->description = $f['description'] ?? '';
        $this->duration = $f['duration'] ?? '';
        $this->level = $f['level'] ?? '';
        $this->modulesCount = (int) $f['modules_count'];
        $this->isFree = (bool) $f['is_free'];
        $this->isCertifying = (bool) $f['is_certifying'];
        $this->fillMedia($f['media_url'] ?? null, $f['media_name'] ?? null);
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
            'description' => $this->description ?: null,
            'duration' => $this->duration ?: null,
            'level' => $this->level ?: null,
            'modules_count' => $this->modulesCount,
            'is_free' => $this->isFree,
            'is_certifying' => $this->isCertifying,
            'media_url' => $this->mediaUrl ?: null,
            'media_name' => $this->mediaName ?: null,
        ];
        $token = Api::token();

        if ($this->editingId) {
            Api::put("/admin/formations/{$this->editingId}", $data, $token);
        } else {
            Api::post('/admin/formations', $data, $token);
        }

        $this->closeForm();
    }

    public function togglePublication(int $id): void
    {
        $f = $this->formations()->firstWhere('id', $id);
        if (! $f) {
            return;
        }

        Api::put("/admin/formations/{$id}", [
            'title' => $f['title'],
            'category' => $f['category'],
            'is_published' => ! $f['is_published'],
        ], Api::token());
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/formations/{$id}", Api::token());
    }

    // ── Modules ───────────────────────────────────────────────────────────

    public function toggleModules(int $formationId): void
    {
        $this->modulesFormationId = $this->modulesFormationId === $formationId ? null : $formationId;
        $this->moduleFormOpen = false;
    }

    protected function modules(): Collection
    {
        if (! $this->modulesFormationId) {
            return collect();
        }

        return Collection::make(Api::get("/admin/formations/{$this->modulesFormationId}/modules", [], Api::token())['modules'] ?? []);
    }

    public function openModuleCreate(): void
    {
        $this->reset(['moduleEditingId', 'moduleTitre', 'moduleDescription', 'moduleVideoUrl', 'moduleDocumentUrl', 'moduleDuree']);
        $this->moduleOrdre = $this->modules()->count() + 1;
        $this->resetValidation();
        $this->moduleFormOpen = true;
    }

    public function openModuleEdit(int $moduleId): void
    {
        $m = $this->modules()->firstWhere('id', $moduleId);
        if (! $m) {
            return;
        }

        $this->moduleEditingId = $m['id'];
        $this->moduleTitre = $m['titre'];
        $this->moduleDescription = $m['description'] ?? '';
        $this->moduleVideoUrl = $m['video_url'] ?? '';
        $this->moduleDocumentUrl = $m['document_url'] ?? '';
        $this->moduleDuree = $m['duree'] ?? '';
        $this->moduleOrdre = (int) $m['ordre'];
        $this->resetValidation();
        $this->moduleFormOpen = true;
    }

    public function closeModuleForm(): void
    {
        $this->moduleFormOpen = false;
        $this->moduleEditingId = null;
    }

    public function saveModule(): void
    {
        $this->validate([
            'moduleTitre' => 'required|string|min:2|max:200',
            'moduleDescription' => 'nullable|string|max:2000',
            'moduleVideoUrl' => 'nullable|url|max:500',
            'moduleDocumentUrl' => 'nullable|url|max:500',
            'moduleDuree' => 'nullable|string|max:50',
            'moduleOrdre' => 'integer|min:0|max:1000',
        ], [], [
            'moduleTitre' => 'titre', 'moduleVideoUrl' => 'lien vidéo', 'moduleDocumentUrl' => 'lien document',
        ]);

        $data = [
            'titre' => $this->moduleTitre,
            'description' => $this->moduleDescription ?: null,
            'video_url' => $this->moduleVideoUrl ?: null,
            'document_url' => $this->moduleDocumentUrl ?: null,
            'duree' => $this->moduleDuree ?: null,
            'ordre' => $this->moduleOrdre,
        ];
        $token = Api::token();

        if ($this->moduleEditingId) {
            Api::put("/admin/formations/{$this->modulesFormationId}/modules/{$this->moduleEditingId}", $data, $token);
        } else {
            Api::post("/admin/formations/{$this->modulesFormationId}/modules", $data, $token);
        }

        $this->closeModuleForm();
    }

    public function deleteModule(int $moduleId): void
    {
        Api::delete("/admin/formations/{$this->modulesFormationId}/modules/{$moduleId}", Api::token());
    }

    public function render()
    {
        $formations = $this->formations()->map(function (array $f) {
            $palette = CategoryPalette::for($f['category']);

            return [
                'id' => $f['id'],
                'titre' => $f['title'],
                'categorie' => $f['category'],
                'duree' => $f['duration'] ?? '—',
                'inscrits' => (int) ($f['enrollments_count'] ?? 0),
                'publiee' => (bool) $f['is_published'],
                'visuel' => "linear-gradient(135deg, {$palette['from']}, {$palette['to']})",
            ];
        });

        return view('livewire.admin.formations', [
            'formations' => $formations,
            'modulesList' => $this->modules(),
        ]);
    }
}
