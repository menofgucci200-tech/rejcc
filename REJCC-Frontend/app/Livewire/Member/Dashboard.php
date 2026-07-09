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
    public array $defisEtat = [true, true, false];

    public function toggleDefi(int $index): void
    {
        $this->defisEtat[$index] = ! $this->defisEtat[$index];
    }

    protected function defisData(): array
    {
        return [
            ['label' => 'Terminer un module de formation', 'xp' => 50],
            ['label' => 'Participer à un atelier', 'xp' => 80],
            ['label' => 'Publier un projet dans la communauté', 'xp' => 120],
        ];
    }

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

        $defis = array_map(function ($d, $i) {
            $fait = $this->defisEtat[$i];
            $d['fait'] = $fait;
            $d['index'] = $i;

            return $d;
        }, $this->defisData(), array_keys($this->defisData()));

        $activites = [
            ['texte' => 'Module « Étude de marché » terminé — Parcours Entrepreneuriat', 'quand' => 'Il y a 2 heures', 'dot' => '#22A85A'],
            ['texte' => 'Nouveau commentaire de Awa Diabaté sur votre business plan', 'quand' => 'Hier · 18 h 40', 'dot' => '#4F6FBF'],
            ['texte' => 'Certificat « Gestion financière de base » obtenu 🎉', 'quand' => 'Il y a 3 jours', 'dot' => '#AC0100'],
            ['texte' => 'Inscription confirmée : Café des entrepreneurs du 18 juillet', 'quand' => 'Il y a 5 jours', 'dot' => '#031D59'],
        ];

        if ($unreadMessages > 0) {
            array_unshift($activites, [
                'texte' => $unreadMessages > 1
                    ? "Vous avez {$unreadMessages} nouveaux messages non lus"
                    : 'Vous avez 1 nouveau message non lu',
                'quand' => 'À l\'instant',
                'dot' => '#4F6FBF',
            ]);
        }

        return view('livewire.member.dashboard', [
            'completion' => $this->profileCompletion($user),
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
