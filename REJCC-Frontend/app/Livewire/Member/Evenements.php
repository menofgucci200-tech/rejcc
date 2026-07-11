<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\CategoryPalette;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Evenements extends Component
{
    public function toggleInscription(int $id): void
    {
        Api::post("/events/{$id}/register", [], Api::token());
    }

    protected function evenements(): Collection
    {
        return Collection::make(Api::get('/events', [], Api::token())['events'] ?? [])
            ->map(function (array $e) {
                $e['starts_at'] = Carbon::parse($e['starts_at']);

                return $e;
            });
    }

    public function render()
    {
        $now = Carbon::now();
        $all = $this->evenements();

        $upcoming = $all
            ->filter(fn (array $e) => $e['starts_at']->isFuture())
            ->sortBy('starts_at')
            ->values()
            ->map(function (array $e) {
                $heure = $e['starts_at']->translatedFormat('H\hi');

                return [
                    'id' => $e['id'],
                    'jour' => $e['starts_at']->day,
                    'date' => $e['starts_at']->translatedFormat('j F'),
                    'tag' => $e['category'],
                    'tagColor' => CategoryPalette::for($e['category'])['tag'],
                    'titre' => $e['title'],
                    'detail' => trim($heure.($e['location'] ? ' · '.$e['location'] : '')),
                    'inscrit' => (bool) $e['registered'],
                ];
            });

        $eventDays = $all
            ->filter(fn (array $e) => $e['starts_at']->isSameMonth($now))
            ->map(fn (array $e) => $e['starts_at']->day)
            ->values()
            ->all();

        $firstOfMonth = $now->copy()->startOfMonth();
        $cells = array_fill(0, $firstOfMonth->dayOfWeekIso - 1, null);
        for ($d = 1; $d <= $now->daysInMonth; $d++) {
            $cells[] = $d;
        }

        return view('livewire.member.evenements', [
            'evenements' => $upcoming,
            'cells' => $cells,
            'eventDays' => $eventDays,
            'today' => $now->day,
            'moisLabel' => ucfirst($now->translatedFormat('F Y')),
        ]);
    }
}
