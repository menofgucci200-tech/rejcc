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

    /** Onglet affiché : membres | discussion. */
    #[Url(except: 'membres')]
    public string $vue = 'membres';

    public array $discussion = [];

    public bool $moderateur = false;

    public string $saisie = '';

    public ?string $erreurDiscussion = null;

    /** Refus d'accès à la discussion (pas membre, pas abonné). */
    public ?array $refusDiscussion = null;

    public function mount(int $groupId): void
    {
        $this->groupId = $groupId;
        if ($this->vue === 'discussion') {
            $this->chargerDiscussion();
        }
    }

    public function updatedQuery(): void
    {
        $this->page = 1;
    }

    public function updatedTri(): void
    {
        $this->page = 1;
    }

    public function updatedVue(): void
    {
        if ($this->vue === 'discussion') {
            $this->chargerDiscussion();
        }
    }

    /** Charge la discussion (ou seulement les nouveaux messages). */
    public function chargerDiscussion(bool $nouveaux = false): void
    {
        $dernier = $nouveaux ? (int) (end($this->discussion)['id'] ?? 0) : 0;
        $result = Api::get("/groups/{$this->groupId}/discussion", $dernier ? ['after' => $dernier] : [], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->refusDiscussion = ['code' => $result['code'] ?? null, 'message' => $result['message'] ?? 'Discussion indisponible.'];
            $this->discussion = [];

            return;
        }
        $this->refusDiscussion = null;
        $this->moderateur = (bool) ($result['moderateur'] ?? false);
        $this->discussion = $dernier
            ? array_merge($this->discussion, $result['messages'] ?? [])
            : ($result['messages'] ?? []);
        \App\Support\NavCompteurs::oublier();
    }

    public function rafraichirDiscussion(): void
    {
        if ($this->vue === 'discussion' && ! $this->refusDiscussion) {
            $this->chargerDiscussion(true);
        }
    }

    public function ecrire(): void
    {
        $this->erreurDiscussion = null;
        $texte = trim($this->saisie);
        if ($texte === '') {
            $this->erreurDiscussion = "Écrivez votre message avant de l'envoyer.";

            return;
        }
        $result = Api::post("/groups/{$this->groupId}/discussion", ['body' => $texte], Api::token());
        if (! ($result['ok'] ?? false)) {
            $this->erreurDiscussion = $result['message'] ?? "Votre message n'a pas pu être envoyé.";

            return;
        }
        $this->saisie = '';
        $this->chargerDiscussion(true);
        $this->dispatch('message-envoye');
    }

    public function supprimerMessage(int $id): void
    {
        $result = Api::delete("/groups/{$this->groupId}/discussion/{$id}", Api::token());
        if ($result['ok'] ?? false) {
            $this->discussion = array_values(array_filter($this->discussion, fn ($m) => $m['id'] !== $id));
        }
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
            // Projets validés du secteur (lien vers la rubrique Projets filtrée).
            'nbProjets' => count(Api::get('/projects', ['groupe' => $this->groupId], Api::token())['projects'] ?? []),
        ]);
    }
}
