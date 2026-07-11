<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\ProjectStatus;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Incubateur extends Component
{
    public function render()
    {
        $projets = Collection::make(Api::get('/incubator', [], Api::token())['projects'] ?? [])
            ->map(fn (array $p) => [
                'titre' => $p['title'],
                'statut' => $p['status'],
                'statutColor' => ProjectStatus::color($p['status']),
                'leve' => (int) $p['funding_raised'],
                'objectif' => (int) ($p['funding_goal'] ?? 0),
                'jalons' => array_map(fn (array $m) => [
                    'label' => $m['label'],
                    'fait' => (bool) $m['done'],
                ], $p['milestones']),
            ])
            ->all();

        return view('livewire.member.incubateur', ['projets' => $projets]);
    }
}
