<?php

namespace App\Livewire;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class EventsExplorer extends Component
{
    public string $type = 'Tous';

    public ?string $selectedDay = null;

    public int $year;

    public int $month;

    public function mount(): void
    {
        $first = $this->fetchEvents()->first();
        $cursor = $first ? $first->starts_at : now();
        $this->year = (int) $cursor->year;
        $this->month = (int) $cursor->month;
    }

    protected function fetchEvents(): Collection
    {
        return Collection::make(Api::get('/public-events')['events'] ?? [])
            ->map(function (array $e) {
                $e['starts_at'] = Carbon::parse($e['starts_at']);

                return (object) $e;
            })
            ->sortBy('starts_at')
            ->values();
    }

    public function selectType(string $type): void
    {
        $this->type = $type;
        $this->selectedDay = null;
    }

    public function selectDay(string $iso): void
    {
        $this->selectedDay = $this->selectedDay === $iso ? null : $iso;
    }

    public function resetDay(): void
    {
        $this->selectedDay = null;
    }

    public function shiftMonth(int $delta): void
    {
        $this->selectedDay = null;
        $cursor = Carbon::create($this->year, $this->month, 1)->addMonthsNoOverflow($delta);
        $this->year = (int) $cursor->year;
        $this->month = (int) $cursor->month;
    }

    public function render()
    {
        $all = $this->fetchEvents();

        $byType = $all->when($this->type !== 'Tous', fn ($c) => $c->where('category', $this->type))->values();

        $eventDays = $byType->pluck('starts_at')->map(fn ($d) => $d->toDateString())->flip();

        $cursor = Carbon::create($this->year, $this->month, 1);
        $daysInMonth = $cursor->daysInMonth;
        $startWeekday = $cursor->dayOfWeekIso - 1; // Lundi = 0

        $cells = array_merge(
            array_fill(0, $startWeekday, null),
            array_map(fn ($d) => $cursor->copy()->day($d)->toDateString(), range(1, $daysInMonth)),
        );

        $agenda = $byType
            ->filter(function ($e) use ($cursor) {
                if ($this->selectedDay) {
                    return $e->starts_at->toDateString() === $this->selectedDay;
                }

                return $e->starts_at->year === $cursor->year && $e->starts_at->month === $cursor->month;
            })
            ->values();

        $types = $all->pluck('category')->unique()->sort()->values();

        return view('livewire.events-explorer', [
            'types' => ['Tous', ...$types],
            'monthLabel' => ucfirst($cursor->locale('fr')->isoFormat('MMMM YYYY')),
            'weekdays' => ['L', 'M', 'M', 'J', 'V', 'S', 'D'],
            'cells' => $cells,
            'eventDays' => $eventDays,
            'agenda' => $agenda,
        ]);
    }
}
