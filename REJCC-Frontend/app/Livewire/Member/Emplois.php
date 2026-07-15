<?php

namespace App\Livewire\Member;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Emplois extends Component
{
    use HandlesMedia;

    public string $filtre = 'tous';

    public bool $showForm = false;

    public string $title = '';

    public string $type = 'emploi';

    public string $entreprise = '';

    public string $site_url = '';

    public string $lieu = '';

    public string $description = '';

    public string $contact = '';

    public string $deadline = '';

    public ?string $message = null;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:4|max:160',
            'type' => 'required|in:emploi,stage,annonce',
            'entreprise' => 'nullable|string|max:160',
            'site_url' => 'nullable|url|max:500',
            'lieu' => 'nullable|string|max:160',
            'description' => 'required|string|min:20|max:3000',
            'contact' => 'nullable|string|max:160',
            'deadline' => 'nullable|date',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Donnez un intitulé à l\'offre.',
            'title.min' => 'L\'intitulé est trop court.',
            'description.required' => 'Décrivez le poste ou le stage.',
            'description.min' => 'Détaillez l\'offre en quelques phrases (20 caractères minimum).',
            'site_url.url' => 'Le lien du site doit être une adresse valide (https://…).',
        ];
    }

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    public function openForm(): void
    {
        $this->reset(['title', 'entreprise', 'site_url', 'lieu', 'description', 'contact', 'deadline']);
        $this->type = 'emploi';
        $this->clearMedia();
        $this->resetValidation();
        $this->message = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function publier(): void
    {
        $data = $this->validate();

        $data['site_url'] = $this->site_url ?: null;
        $data['media_url'] = $this->mediaUrl ?: null;
        $data['media_name'] = $this->mediaName ?: null;

        $result = Api::post('/opportunities', array_filter($data, fn ($v) => $v !== null && $v !== ''), Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->addError('title', $result['message'] ?? 'La publication a échoué.');

            return;
        }

        $this->closeForm();
        $this->message = 'Offre publiée ! Elle est visible par tous les membres.';
    }

    public function render()
    {
        $offres = Collection::make(Api::get('/opportunities', [], Api::token())['opportunities'] ?? [])
            ->map(fn (array $o) => [
                'id' => $o['id'],
                'titre' => $o['title'],
                'description' => $o['description'],
                'type' => strtolower($o['type']),
                'entreprise' => $o['entreprise'] ?? null,
                'site_url' => $o['site_url'] ?? null,
                'lieu' => $o['lieu'] ?? null,
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
