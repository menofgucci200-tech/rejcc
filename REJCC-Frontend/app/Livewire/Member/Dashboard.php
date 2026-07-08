<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member')]
class Dashboard extends Component
{
    protected function profileCompletion(object $user): int
    {
        $fields = [$user->prenom, $user->nom, $user->email, $user->telephone, $user->ville, $user->secteur, $user->profil, $user->bio ?? null];
        $filled = count(array_filter($fields));

        return (int) round(($filled / count($fields)) * 100);
    }

    public function render()
    {
        $token = Api::token();
        $user = Api::user();

        $conversations = Collection::make(Api::get('/messages', [], $token)['conversations'] ?? []);
        $unreadMessages = $conversations->sum('unread');

        $allDocs = Collection::make(Api::get('/documents', [], $token)['documents'] ?? []);
        $docs = $allDocs->take(4)->map(fn ($d) => (object) $d);

        $allMembers = Collection::make(Api::get('/members', [], $token)['members'] ?? [])
            ->reject(fn ($m) => $m['id'] === $user->id);
        $members = $allMembers->take(4)->map(fn ($m) => (object) $m);

        $allEvents = Collection::make(Api::get('/events', [], $token)['events'] ?? [])
            ->map(function ($e) {
                $e['starts_at'] = Carbon::parse($e['starts_at']);

                return (object) $e;
            });
        $upcomingEvents = $allEvents->filter(fn ($e) => $e->starts_at->isFuture())->sortBy('starts_at')->take(4)->values();

        $sectorsCount = count(Api::get('/sectors')['sectors'] ?? []);
        $villesCount = $allMembers->pluck('ville')->filter()->unique()->count();

        $networkStats = [
            ['icon' => 'users', 'value' => $allMembers->count() + 1, 'suffix' => '+', 'label' => 'Membres actifs', 'color' => '#9DB2EE'],
            ['icon' => 'globe', 'value' => $sectorsCount, 'suffix' => '', 'label' => 'Secteurs représentés', 'color' => '#34D399'],
            ['icon' => 'map-pin', 'value' => $villesCount, 'suffix' => '', 'label' => 'Villes couvertes', 'color' => '#F2A33C'],
            ['icon' => 'calendar', 'value' => $allEvents->count(), 'suffix' => '+', 'label' => 'Événements organisés', 'color' => '#A78BFA'],
        ];

        return view('livewire.member.dashboard', [
            'completion' => $this->profileCompletion($user),
            'unreadMessages' => $unreadMessages,
            'docs' => $docs,
            'members' => $members,
            'upcomingEvents' => $upcomingEvents,
            'networkStats' => $networkStats,
        ]);
    }
}
