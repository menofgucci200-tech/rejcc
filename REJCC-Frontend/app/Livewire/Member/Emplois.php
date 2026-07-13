<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Emplois extends Component
{
    public string $filtre = 'tous';

    public bool $showForm = false;

    public string $title = '';

    public string $type = 'emploi';

    public string $description = '';

    public string $contact = '';

    public string $deadline = '';

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:4|max:160',
            'type' => 'required|in:emploi,stage,annonce',
            'description' => 'required|string|min:20|max:3000',
            'contact' => 'nullable|string|max:160',
            'deadline' => 'nullable|date',
        ];
    }

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    public function openForm(): void
    {
        $this->reset(['title', 'description', 'contact', 'deadline']);
        $this->type = 'emploi';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function publier(): void
    {
        $data = $this->validate();

        Api::post('/opportunities', array_filter($data), Api::token());

        $this->closeForm();
    }

    public function render()
    {
        $offres = Collection::make(Api::get('/opportunities', [], Api::token())['opportunities'] ?? [])
            ->map(fn (array $o) => [
                'id' => $o['id'],
                'titre' => $o['title'],
                'description' => $o['description'],
                'type' => strtolower($o['type']),
                'auteur' => $o['author'] ?? 'REJCC',
                'contact' => $o['contact'],
                'media' => $o['media_url'] ?? null,
                'deadline' => $o['deadline'] ? Carbon::parse($o['deadline'])->translatedFormat('j F Y') : null,
                'date' => Carbon::parse($o['created_at'])->diffForHumans(),
            ])
            ->when($this->filtre !== 'tous', fn ($c) => $c->where('type', $this->filtre))
            ->values();

        return view('livewire.member.emplois', ['offres' => $offres]);
    }
}
