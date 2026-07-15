<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\Content\MembershipContent;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Directory extends Component
{
    public string $query = '';

    public string $filtre = 'tous';

    public int $page = 1;

    public function updatedQuery(): void
    {
        $this->page = 1;
    }

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
        $this->page = 1;
    }

    public function gotoPage(int $p): void
    {
        $this->page = max(1, $p);
    }

    public function render()
    {
        // Recherche, filtre et pagination côté serveur (l'annuaire peut
        // compter plusieurs milliers de membres).
        $params = ['page' => $this->page];
        if (trim($this->query) !== '') {
            $params['q'] = trim($this->query);
        }
        if ($this->filtre !== 'tous') {
            $params['profil'] = $this->filtre;
        }

        $result = Api::get('/members', $params, Api::token());

        $members = Collection::make($result['members'] ?? [])
            ->map(fn ($m) => (object) $m);

        return view('livewire.member.directory', [
            'members' => $members,
            'meta' => $result['meta'] ?? [],
            'profiles' => MembershipContent::profiles(),
        ]);
    }
}
