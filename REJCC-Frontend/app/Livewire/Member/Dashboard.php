<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Dashboard extends Component
{
    protected function profileCompletion(object $user): int
    {
        $fields = [$user->prenom, $user->nom, $user->email, $user->telephone, $user->ville, $user->secteur, $user->profil, $user->bio ?? null];
        $filled = count(array_filter($fields));

        return (int) round(($filled / count($fields)) * 100);
    }

    /**
     * Défis calculés depuis les vraies actions du membre — plus de cases à
     * cocher factices : chaque défi se valide tout seul quand l'action est faite.
     */
    protected function defis(Collection $formations, Collection $events, int $completion): array
    {
        return [
            [
                'label' => 'S\'inscrire à une formation du catalogue',
                'xp' => 50,
                'fait' => $formations->isNotEmpty(),
            ],
            [
                'label' => 'Faire avancer une formation (valider un module)',
                'xp' => 80,
                'fait' => $formations->contains(fn (array $f) => $f['completed'] || $f['progress'] > 0),
            ],
            [
                'label' => 'S\'inscrire à un événement du réseau',
                'xp' => 80,
                'fait' => $events->contains(fn (array $e) => ! empty($e['registered'])),
            ],
            [
                'label' => 'Compléter son profil à 100 %',
                'xp' => 120,
                'fait' => $completion >= 100,
            ],
        ];
    }

    public function render()
    {
        $token = Api::token();
        $user = Api::user();
        $completion = $this->profileCompletion($user);

        $conversations = Collection::make(Api::get('/messages', [], $token)['conversations'] ?? []);
        $unreadMessages = $conversations->sum('unread');

        $docs = Collection::make(Api::get('/documents', [], $token)['documents'] ?? [])
            ->take(4)->map(fn ($d) => (object) $d);

        $members = Collection::make(Api::get('/members', [], $token)['members'] ?? [])
            ->reject(fn ($m) => $m['id'] === $user->id)
            ->take(4)->map(fn ($m) => (object) $m);

        $events = Collection::make(Api::get('/events', [], $token)['events'] ?? []);
        $upcomingEvents = $events
            ->map(function ($e) {
                $e['starts_at'] = Carbon::parse($e['starts_at']);

                return (object) $e;
            })
            ->filter(fn ($e) => $e->starts_at->isFuture())
            ->sortBy('starts_at')->take(4)->values();

        $formations = Collection::make(Api::get('/my-formations', [], $token)['formations'] ?? []);

        $defis = $this->defis($formations, $events, $completion);

        $activites = Collection::make(Api::get('/my-activity', [], $token)['activity'] ?? [])
            ->map(fn (array $a) => [
                'texte' => $a['text'],
                'quand' => ucfirst(Carbon::parse($a['at'])->diffForHumans()),
                'dot' => $a['color'],
            ])
            ->all();

        if ($unreadMessages > 0) {
            array_unshift($activites, [
                'texte' => $unreadMessages > 1
                    ? "Vous avez {$unreadMessages} nouveaux messages non lus"
                    : 'Vous avez 1 nouveau message non lu',
                'quand' => 'À l\'instant',
                'dot' => '#4F6FBF',
            ]);
        }

        if ($activites === []) {
            $activites[] = [
                'texte' => 'Bienvenue ! Inscrivez-vous à une formation ou un événement pour démarrer votre parcours.',
                'quand' => 'Pour commencer',
                'dot' => '#4F6FBF',
            ];
        }

        return view('livewire.member.dashboard', [
            'completion' => $completion,
            'unreadMessages' => $unreadMessages,
            'docs' => $docs,
            'members' => $members,
            'upcomingEvents' => $upcomingEvents,
            'defis' => $defis,
            'defisFaits' => collect($defis)->where('fait', true)->count(),
            'activites' => $activites,
        ]);
    }
}
