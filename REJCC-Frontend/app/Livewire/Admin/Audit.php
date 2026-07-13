<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Audit extends Component
{
    public string $recherche = '';

    public function render()
    {
        $logs = Collection::make(Api::get('/admin/audit', ['q' => $this->recherche], Api::token())['logs'] ?? [])
            ->map(function (array $l) {
                $at = Carbon::parse($l['created_at']);

                return [
                    'actor' => $l['actor'] ?? '—',
                    'action' => $l['action'],
                    'target' => $l['target'] ?: '—',
                    'method' => $l['method'],
                    'ip' => $l['ip'] ?? '—',
                    'quand' => $at->translatedFormat('j M Y · H:i'),
                    'depuis' => $at->diffForHumans(),
                    'couleur' => match ($l['action']) {
                        'Création', 'Acceptation' => ['#22A85A', '#EAF6EE'],
                        'Suppression', 'Rejet' => ['#AC0100', '#F9E9E9'],
                        default => ['#4F6FBF', '#E8EDF8'],
                    },
                ];
            });

        return view('livewire.admin.audit', ['logs' => $logs]);
    }
}
