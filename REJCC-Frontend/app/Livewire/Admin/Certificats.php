<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Certificats extends Component
{
    private const PALETTE = [
        ['from' => '#031D59', 'to' => '#4F6FBF'],
        ['from' => '#AC0100', 'to' => '#D95B5A'],
        ['from' => '#4F6FBF', 'to' => '#8FB0FF'],
    ];

    public function render()
    {
        $certificats = Collection::make(Api::get('/admin/certificates', [], Api::token())['certificates'] ?? [])
            ->values()
            ->map(function (array $c, int $i) {
                $initiales = Collection::make(explode(' ', $c['member']))
                    ->filter()
                    ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
                    ->take(2)
                    ->implode('');

                return [
                    'membre' => $c['member'],
                    'email' => $c['email'],
                    'formation' => $c['title'],
                    'reference' => $c['reference'],
                    'date' => Carbon::parse($c['issued_at'])->translatedFormat('j F Y'),
                    'initiales' => $initiales ?: '—',
                    ...self::PALETTE[$i % count(self::PALETTE)],
                ];
            });

        return view('livewire.admin.certificats', ['certificats' => $certificats]);
    }
}
