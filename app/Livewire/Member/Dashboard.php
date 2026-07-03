<?php

namespace App\Livewire\Member;

use App\Models\Document;
use App\Models\Event;
use App\Models\Message;
use App\Models\Sector;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member')]
class Dashboard extends Component
{
    protected function profileCompletion(User $user): int
    {
        $fields = [$user->prenom, $user->nom, $user->email, $user->telephone, $user->ville, $user->secteur, $user->profil, $user->bio];
        $filled = count(array_filter($fields));

        return (int) round(($filled / count($fields)) * 100);
    }

    public function render()
    {
        $user = auth()->user();
        $me = $user->id;

        $unreadMessages = Message::where('recipient_id', $me)->whereNull('read_at')->count();
        $docs = Document::orderBy('category')->orderBy('title')->limit(4)->get();
        $members = User::where('role', 'member')->where('id', '!=', $me)->orderBy('prenom')->limit(4)->get();
        $upcomingEvents = Event::where('starts_at', '>=', now())->orderBy('starts_at')->limit(4)->get();

        $networkStats = [
            ['icon' => 'users', 'value' => User::where('role', 'member')->count(), 'suffix' => '+', 'label' => 'Membres actifs', 'color' => '#9DB2EE'],
            ['icon' => 'globe', 'value' => Sector::count(), 'suffix' => '', 'label' => 'Secteurs représentés', 'color' => '#34D399'],
            ['icon' => 'map-pin', 'value' => User::where('role', 'member')->whereNotNull('ville')->distinct('ville')->count('ville'), 'suffix' => '', 'label' => 'Villes couvertes', 'color' => '#F2A33C'],
            ['icon' => 'calendar', 'value' => Event::count(), 'suffix' => '+', 'label' => 'Événements organisés', 'color' => '#A78BFA'],
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
