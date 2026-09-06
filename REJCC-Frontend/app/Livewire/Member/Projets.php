<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\ProjectStatus;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Projets extends Component
{
    public bool $showForm = false;

    public string $title = '';

    public string $description = '';

    public int $membersCount = 1;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:4|max:160',
            'description' => 'required|string|min:20|max:3000',
            'membersCount' => 'required|integer|min:1|max:500',
        ];
    }

    public function openForm(): void
    {
        $this->reset(['title', 'description']);
        $this->membersCount = 1;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function proposer(): void
    {
        $this->validate();

        Api::post('/projects', [
            'title' => $this->title,
            'description' => $this->description,
            'members_count' => $this->membersCount,
        ], Api::token());

        $this->closeForm();
    }

    public function render()
    {
        if (! (Api::user()->subscription_active ?? false)) {
            return view('livewire.member.projets', ['locked' => true, 'projets' => []]);
        }

        $projets = Collection::make(Api::get('/projects', [], Api::token())['projects'] ?? [])
            ->map(fn (array $p) => [
                'titre' => $p['title'],
                'statut' => $p['status'],
                'statutColor' => ProjectStatus::color($p['status']),
                'membres' => (int) $p['members_count'],
                'description' => $p['description'],
                'porteur' => $p['porteur'],
                'mien' => (bool) $p['mine'],
            ])
            ->all();

        return view('livewire.member.projets', ['projets' => $projets]);
    }
}
