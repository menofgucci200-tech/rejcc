<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use App\Support\CategoryPalette;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Evenements extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $category = '';

    public string $startsAt = '';

    public string $timeLabel = '';

    public string $location = '';

    public string $excerpt = '';

    public string $description = '';

    public ?int $capacity = null;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:160',
            'category' => 'required|string|min:2|max:60',
            'startsAt' => 'required|date',
            'timeLabel' => 'nullable|string|max:60',
            'location' => 'nullable|string|max:160',
            'excerpt' => 'nullable|string|max:300',
            'description' => 'nullable|string|max:3000',
            'capacity' => 'nullable|integer|min:1',
        ];
    }

    protected function evenements(): Collection
    {
        return Collection::make(Api::get('/admin/events', [], Api::token())['events'] ?? []);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'category', 'startsAt', 'timeLabel', 'location', 'excerpt', 'description', 'capacity']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $e = $this->evenements()->firstWhere('id', $id);
        if (! $e) {
            return;
        }

        $this->editingId = $e['id'];
        $this->title = $e['title'];
        $this->category = $e['category'];
        $this->startsAt = Carbon::parse($e['starts_at'])->format('Y-m-d\TH:i');
        $this->timeLabel = $e['time_label'] ?? '';
        $this->location = $e['location'] ?? '';
        $this->excerpt = $e['excerpt'] ?? '';
        $this->description = $e['description'] ?? '';
        $this->capacity = $e['capacity'];
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
            'starts_at' => $this->startsAt,
            'time_label' => $this->timeLabel ?: null,
            'location' => $this->location ?: null,
            'excerpt' => $this->excerpt ?: null,
            'description' => $this->description ?: null,
            'capacity' => $this->capacity,
        ];
        $token = Api::token();

        if ($this->editingId) {
            Api::put("/admin/events/{$this->editingId}", $data, $token);
        } else {
            Api::post('/admin/events', $data, $token);
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/events/{$id}", Api::token());
    }

    public function render()
    {
        $evenements = $this->evenements()->map(function (array $e) {
            $starts = Carbon::parse($e['starts_at']);

            return [
                'id' => $e['id'],
                'jour' => $starts->format('d'),
                'mois' => mb_strtoupper($starts->translatedFormat('M')),
                'passe' => $starts->isPast(),
                'tag' => mb_strtoupper($e['category']),
                'tagColor' => CategoryPalette::for($e['category'])['tag'],
                'titre' => $e['title'],
                'detail' => implode(' · ', array_filter([$e['location'], $e['time_label']])),
                'inscrits' => (int) ($e['registrations_count'] ?? 0),
            ];
        });

        return view('livewire.admin.evenements', ['evenements' => $evenements]);
    }
}
