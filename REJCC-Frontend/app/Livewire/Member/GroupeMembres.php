<?php

namespace App\Livewire\Member;

use App\Livewire\Concerns\GereFichePro;
use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
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
    use GereFichePro;

    public int $groupId;

    #[Url(as: 'q', except: '')]
    public string $query = '';

    /** Tri : nom (défaut), note, recents. */
    #[Url(except: 'nom')]
    public string $tri = 'nom';

    public int $page = 1;

    public function mount(int $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function updatedQuery(): void
    {
        $this->page = 1;
    }

    public function updatedTri(): void
    {
        $this->page = 1;
    }

    public function gotoPage(int $p): void
    {
        $this->page = max(1, $p);
    }

    /** Ouvre la fiche professionnelle complète du membre dans ce groupe. */
    public function voirProfil(int $id): void
    {
        $this->voirFiche($this->groupId, $id);
    }

    public function render()
    {
        if (! (Api::user()->subscription_active ?? false)) {
            // Aperçu sans données personnelles : chiffres et services proposés.
            $apercu = Api::get("/groups/{$this->groupId}/apercu", [], Api::token())['apercu'] ?? null;

            return view('livewire.member.groupe-membres', ['locked' => true, 'apercu' => $apercu, 'members' => collect(), 'meta' => [], 'groupe' => $apercu['group'] ?? null]);
        }

        $params = ['page' => $this->page, 'tri' => $this->tri];
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
