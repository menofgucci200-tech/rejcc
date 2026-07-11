<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Mentors extends Component
{
    public function render()
    {
        $mentors = Collection::make(Api::get('/admin/members', [], Api::token())['members'] ?? [])
            ->where('role', 'mentor')
            ->values()
            ->map(fn ($m) => [
                'id' => $m['id'],
                'nom' => trim(($m['prenom'] ?? '').' '.($m['nom'] ?? '')) ?: $m['email'],
                'email' => $m['email'],
                'telephone' => $m['telephone'] ?? '—',
                'ville' => $m['ville'] ?? null,
                'secteur' => $m['secteur'] ?? null,
                'actif' => (bool) ($m['is_active'] ?? true),
                'depuis' => Carbon::parse($m['created_at'])->translatedFormat('j M Y'),
                'initiales' => mb_strtoupper(mb_substr($m['prenom'] ?? $m['email'], 0, 1).mb_substr($m['nom'] ?? '', 0, 1)),
            ]);

        return view('livewire.admin.mentors', ['mentors' => $mentors]);
    }
}
