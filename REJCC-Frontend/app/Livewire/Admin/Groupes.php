<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Administration des groupes sectoriels : liste ordonnée, création et
 * modification (identité, référent, annonce épinglée),
 * membres de chaque groupe (retrait, export) et modération des avis.
 */
#[Layout('layouts.admin-light')]
class Groupes extends Component
{
    #[Url(except: 'groupes')]
    public string $onglet = 'groupes';

    public string $filtreAvis = 'signales';

    public int $pageAvis = 1;

    public ?string $message = null;

    // Formulaire de groupe
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $icone = 'network';

    public string $couleur = '#031D59';

    public ?int $referentId = null;

    public string $annonce = '';

    public bool $notifier = true;

    public ?string $erreur = null;

    /** Membres du groupe en cours de modification (choix du référent). */
    public array $membresForm = [];

    // Fenêtre des membres d'un groupe
    public ?int $membresGroupId = null;

    public string $rechercheMembre = '';

    protected function liste(): array
    {
        return Api::get('/admin/groups', [], Api::token());
    }

    public function openCreate(): void
    {
        $this->message = null;
        $this->reset(['editingId', 'name', 'description', 'referentId', 'annonce', 'erreur', 'membresForm']);
        $this->icone = 'network';
        $this->couleur = '#031D59';
        $this->notifier = true;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->message = null;
        $g = collect($this->liste()['groups'] ?? [])->firstWhere('id', $id);
        if (! $g) {
            return;
        }
        $this->editingId = $id;
        $this->name = $g['name'];
        $this->description = (string) ($g['description'] ?? '');
        $this->icone = $g['icone'];
        $this->couleur = $g['couleur'];
        $this->referentId = $g['referent']['id'] ?? null;
        $this->annonce = (string) ($g['annonce'] ?? '');
        $this->notifier = true;
        $this->erreur = null;
        $this->membresForm = collect(Api::get("/admin/groups/{$id}/members", [], Api::token())['members'] ?? [])
            ->filter(fn ($m) => $m['actif'])
            ->map(fn ($m) => ['id' => $m['id'], 'nom' => $m['nom']])->values()->all();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        $data = [
            'name' => trim($this->name),
            'description' => trim($this->description) ?: null,
            'icone' => $this->icone,
            'couleur' => $this->couleur,
            'referent_id' => $this->referentId ?: null,
            'annonce' => trim($this->annonce) ?: null,
            'notifier' => $this->notifier,
        ];

        $result = $this->editingId
            ? Api::put("/admin/groups/{$this->editingId}", $data, Api::token())
            : Api::post('/admin/groups', $data, Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->erreur = $result['message'] ?? 'Une erreur est survenue.';

            return;
        }

        $notifies = (int) ($result['notifies'] ?? 0);
        $this->message = ($this->editingId ? 'Groupe mis à jour.' : 'Groupe créé.')
            .($notifies ? " Annonce envoyée à {$notifies} membre".($notifies > 1 ? 's' : '').'.' : '');
        $this->closeForm();
    }

    public function move(int $id, string $direction): void
    {
        $this->message = null;
        Api::post("/admin/groups/{$id}/move", ['direction' => $direction], Api::token());
    }

    public function delete(int $id): void
    {
        $result = Api::delete("/admin/groups/{$id}", Api::token());
        $this->message = ($result['ok'] ?? false) ? 'Groupe supprimé.' : ($result['message'] ?? 'Suppression impossible.');
    }

    public function voirMembres(int $id): void
    {
        $this->message = null;
        $this->membresGroupId = $id;
        $this->rechercheMembre = '';
    }

    public function fermerMembres(): void
    {
        $this->membresGroupId = null;
    }

    public function retirerMembre(int $userId, string $motif = ''): void
    {
        $result = Api::delete("/admin/groups/{$this->membresGroupId}/members/{$userId}", Api::token(), ['motif' => $motif]);
        $this->message = ($result['ok'] ?? false) ? 'Le membre a été retiré du groupe et prévenu.' : ($result['message'] ?? 'Une erreur est survenue.');
    }

    public function updatedOnglet(): void
    {
        $this->message = null;
    }

    public function updatedFiltreAvis(): void
    {
        $this->pageAvis = 1;
    }

    public function gotoPage(int $p): void
    {
        $this->pageAvis = max(1, $p);
    }

    public function moderer(int $id, bool $masque): void
    {
        Api::put("/admin/avis/{$id}", ['masque' => $masque], Api::token());
        AdminNav::oublier();
        $this->message = $masque ? "Avis masqué ; son auteur en est informé." : 'Avis conservé, signalement classé.';
    }

    public function supprimerAvis(int $id): void
    {
        Api::delete("/admin/avis/{$id}", Api::token());
        AdminNav::oublier();
        $this->message = 'Avis supprimé.';
    }

    public function render()
    {
        $liste = $this->liste();
        $groups = Collection::make($liste['groups'] ?? []);

        $membres = collect();
        $groupeMembres = null;
        if ($this->membresGroupId) {
            $res = Api::get("/admin/groups/{$this->membresGroupId}/members", [], Api::token());
            $groupeMembres = $res['group'] ?? null;
            $q = mb_strtolower(trim($this->rechercheMembre));
            $membres = collect($res['members'] ?? [])->when($q !== '', fn ($c) => $c->filter(
                fn ($m) => str_contains(mb_strtolower($m['nom'].' '.$m['specialite'].' '.$m['ville'].' '.$m['email']), $q)
            ))->values();
        }

        $avis = null;
        if ($this->onglet === 'avis') {
            $avis = Api::get('/admin/avis', ['filtre' => $this->filtreAvis, 'page' => $this->pageAvis], Api::token());
        }

        return view('livewire.admin.groupes', [
            'groups' => $groups,
            'icones' => $liste['icones'] ?? ['network'],
            'avisSignales' => (int) ($liste['avis_signales'] ?? 0),
            'groupeMembres' => $groupeMembres,
            'membres' => $membres,
            'avis' => $avis,
        ]);
    }
}
