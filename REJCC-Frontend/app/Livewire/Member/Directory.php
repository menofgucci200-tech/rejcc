<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\Content\MembershipContent;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Directory extends Component
{
    #[Url(as: 'q', except: '')]
    public string $query = '';

    #[Url(as: 'filtre', except: 'tous')]
    public string $filtre = 'tous';

    #[Url(as: 'secteur', except: '')]
    public string $secteur = '';

    #[Url(as: 'ville', except: '')]
    public string $ville = '';

    #[Url(as: 'groupe', except: '')]
    public string $groupe = '';

    #[Url(as: 'tri', except: 'nom')]
    public string $tri = 'nom';

    public int $page = 1;

    public ?array $detail = null;

    public function updated(string $propriete): void
    {
        if (in_array($propriete, ['query', 'secteur', 'ville', 'groupe', 'tri'], true)) {
            $this->page = 1;
        }
    }

    public function reinitialiser(): void
    {
        $this->reset(['query', 'filtre', 'secteur', 'ville', 'groupe', 'tri']);
        $this->page = 1;
    }

    public function voirProfil(int $id): void
    {
        $result = Api::get("/members/{$id}", [], Api::token());
        $this->detail = ($result['ok'] ?? false) ? $result['member'] : null;
    }

    public function fermerProfil(): void
    {
        $this->detail = null;
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
        if (! (Api::user()->subscription_active ?? false)) {
            return view('livewire.member.directory', ['locked' => true, 'members' => collect(), 'meta' => [], 'profiles' => MembershipContent::profiles()]);
        }

        // Recherche, filtre et pagination côté serveur (l'annuaire peut
        // compter plusieurs milliers de membres).
        $params = ['page' => $this->page];
        if (trim($this->query) !== '') {
            $params['q'] = trim($this->query);
        }
        if ($this->filtre === 'mentors') {
            $params['mentors'] = 1;
        } elseif ($this->filtre !== 'tous') {
            $params['profil'] = $this->filtre;
        }

        $params += array_filter([
            'secteur' => $this->secteur,
            'ville' => $this->ville,
            'groupe' => $this->groupe,
            'tri' => $this->tri === 'recents' ? 'recents' : null,
        ]);

        $result = Api::get('/members', $params, Api::token());

        $members = Collection::make($result['members'] ?? [])
            ->map(fn ($m) => (object) $m);

        return view('livewire.member.directory', [
            'members' => $members,
            'meta' => $result['meta'] ?? [],
            'filtres' => $result['filtres'] ?? ['secteurs' => [], 'villes' => [], 'groupes' => []],
            'filtresActifs' => trim($this->query) !== '' || $this->filtre !== 'tous' || $this->secteur !== '' || $this->ville !== '' || $this->groupe !== '',
            'profiles' => MembershipContent::profiles(),
        ]);
    }
}
