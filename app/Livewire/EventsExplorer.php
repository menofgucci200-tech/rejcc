<?php

namespace App\Livewire;

use App\Models\Event;
use Carbon\Carbon;
use Livewire\Component;

class EventsExplorer extends Component
{
    public string $type = 'Tous';

    public ?string $selectedDay = null;

    public int $year;

    public int $month;

    public function mount(): void
    {
        $first = Event::orderBy('starts_at')->value('starts_at');
        $cursor = $first ? Carbon::parse($first) : now();
        $this->year = (int) $cursor->year;
        $this->month = (int) $cursor->month;
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
        $byType = Event::query()
            ->when($this->type !== 'Tous', fn ($q) => $q->where('category', $this->type))
            ->orderBy('starts_at')
            ->get();

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

        $types = Event::query()->distinct()->orderBy('category')->pluck('category');

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
