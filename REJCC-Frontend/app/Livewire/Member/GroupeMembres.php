<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Trombinoscope d'un groupe sectoriel : liste des membres avec leur
 * spécialité dans le domaine, pour trouver le bon contact (ex. un plombier
 * disponible dans sa ville). Réservé aux abonnés à jour (cf. middleware
 * `sub.active` côté API).
 */
#[Layout('layouts.member-light')]
class GroupeMembres extends Component
{
    public int $groupId;

    public string $query = '';

    public int $page = 1;

    public ?array $detail = null;

    public function mount(int $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function updatedQuery(): void
    {
        $this->page = 1;
    }

    public function gotoPage(int $p): void
    {
        $this->page = max(1, $p);
    }

    public function voirProfil(int $id, ?string $specialite = null): void
    {
        $result = Api::get("/members/{$id}", [], Api::token());
        $this->detail = ($result['ok'] ?? false) ? [...$result['member'], 'specialite' => $specialite] : null;
    }

    public function fermerProfil(): void
    {
        $this->detail = null;
    }

    public function render()
    {
        if (! (Api::user()->subscription_active ?? false)) {
            return view('livewire.member.groupe-membres', ['locked' => true, 'members' => collect(), 'meta' => [], 'groupe' => null]);
        }

        $params = ['page' => $this->page];
        if (trim($this->query) !== '') {
            $params['q'] = trim($this->query);
        }

        $result = Api::get("/groups/{$this->groupId}/members", $params, Api::token());

        return view('livewire.member.groupe-membres', [
            'groupe' => $result['group'] ?? null,
            'members' => Collection::make($result['members'] ?? []),
            'meta' => $result['meta'] ?? [],
        ]);
    }
}
