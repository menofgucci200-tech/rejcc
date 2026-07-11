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

    public ?int $fundingGoal = null;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:4|max:160',
            'description' => 'required|string|min:20|max:3000',
            'membersCount' => 'required|integer|min:1|max:500',
            'fundingGoal' => 'nullable|integer|min:0',
        ];
    }

    public function openForm(): void
    {
        $this->reset(['title', 'description', 'fundingGoal']);
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

        Api::post('/projects', array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'members_count' => $this->membersCount,
            'funding_goal' => $this->fundingGoal,
        ], fn ($v) => $v !== null), Api::token());

        $this->closeForm();
    }

    public function render()
    {
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
