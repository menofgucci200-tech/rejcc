<?php

namespace App\Livewire\Member;

use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Evenements extends Component
{
    protected function evenements(): array
    {
        return [
            ['jour' => 12, 'tag' => 'Formation', 'tagColor' => '#4F6FBF', 'titre' => 'Atelier leadership chrétien', 'detail' => '10h00 · En ligne'],
            ['jour' => 18, 'tag' => 'Réseautage', 'tagColor' => '#AC0100', 'titre' => 'Rencontre mensuelle des membres', 'detail' => '18h00 · Yaoundé, Siège REJCC'],
            ['jour' => 23, 'tag' => 'Spiritualité', 'tagColor' => '#22A85A', 'titre' => 'Retraite de prière entrepreneurs', 'detail' => 'Journée complète · Centre Emmaüs'],
            ['jour' => 29, 'tag' => 'Incubateur', 'tagColor' => '#F5A623', 'titre' => 'Pitch day — projets incubés', 'detail' => '14h00 · En ligne'],
        ];
    }

    public function render()
    {
        $now = Carbon::now();
        $evenements = $this->evenements();
        $eventDays = collect($evenements)->pluck('jour')->all();

        $firstOfMonth = $now->copy()->startOfMonth();
        $daysInMonth = $now->daysInMonth;
        $startOffset = ($firstOfMonth->dayOfWeekIso - 1);

        $cells = [];
        for ($i = 0; $i < $startOffset; $i++) {
            $cells[] = null;
        }
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $cells[] = $d;
        }

        return view('livewire.member.evenements', [
            'evenements' => $evenements,
            'cells' => $cells,
            'eventDays' => $eventDays,
            'today' => $now->day,
            'moisLabel' => ucfirst($now->translatedFormat('F Y')),
        ]);
    }
}
