<?php

namespace App\Livewire\Member;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Projets du réseau : projets validés des membres, mes projets (tous
 * statuts, avec le retour de l'équipe), fiche complète et formulaire
 * (proposer, compléter, resoumettre, retirer).
 */
#[Layout('layouts.member-light')]
class Projets extends Component
{
    use HandlesMedia;

    /** Projet ouvert (lien partageable, notification). */
    #[Url(as: 'projet', except: null)]
    public ?int $focus = null;

    #[Url(except: 'reseau')]
    public string $onglet = 'reseau';

    public ?array $fiche = null;

    public ?string $message = null;

    public ?string $erreur = null;

    // ── Formulaire ───────────────────────────────────────────────────────
    public bool $showForm = false;

    public ?int $editingId = null;

    public ?string $statutEdition = null;

    public string $title = '';

    public string $accroche = '';

    public string $groupId = '';

    public string $stade = 'idee';

    public string $ville = '';

    public string $description = '';

    public string $probleme = '';

    public string $solution = '';

    public string $cible = '';

    public string $impact = '';

    public array $besoins = [];

    public string $lien = '';

    public int $membersCount = 1;

    // ── Collaborer (fiche) ───────────────────────────────────────────────
    public bool $rejoindreOuvert = false;

    public string $messageRejoindre = '';

    public string $roleRejoindre = '';

    public string $rechercheCandidat = '';

    public string $roleInvite = '';

    public string $texteAvancee = '';

    public ?string $infoFiche = null;

    public function mount(): void
    {
        if ($this->focus) {
            $this->voir($this->focus);
        }
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:4|max:160',
            'accroche' => 'nullable|string|max:200',
            'groupId' => 'required',
            'description' => 'required|string|min:20|max:3000',
            'probleme' => 'nullable|string|max:2000',
            'solution' => 'nullable|string|max:2000',
            'cible' => 'nullable|string|max:1000',
            'impact' => 'nullable|string|max:1000',
            'ville' => 'nullable|string|max:80',
            'lien' => 'nullable|url|max:500',
            'membersCount' => 'required|integer|min:1|max:500',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Donnez un nom à votre projet.',
            'title.min' => 'Le nom du projet est trop court (4 caractères minimum).',
            'groupId.required' => 'Choisissez le secteur de votre projet.',
            'description.required' => 'Décrivez votre projet en quelques phrases.',
            'description.min' => 'La description est trop courte (20 caractères minimum).',
            'lien.url' => 'Le lien doit être une adresse complète (https://…).',
        ];
    }

    public function setOnglet(string $o): void
    {
        $this->onglet = in_array($o, ['reseau', 'mes', 'equipes', 'suivis'], true) ? $o : 'reseau';
    }

    // ── Fiche ────────────────────────────────────────────────────────────

    public function voir(int $id, bool $garderInfo = false): void
    {
        if (! $garderInfo) {
            $this->infoFiche = null;
            $this->rejoindreOuvert = false;
            $this->rechercheCandidat = '';
            $this->texteAvancee = '';
        }
        $r = Api::get("/projects/{$id}", [], Api::token());
        if ($r['ok'] ?? false) {
            $this->fiche = $r['project'];
            $this->focus = $id;
        } else {
            $this->fiche = null;
            $this->focus = null;
            $this->erreur = $r['message'] ?? 'Projet introuvable.';
        }
    }

    public function fermerFiche(): void
    {
        $this->fiche = null;
        $this->focus = null;
    }

    // ── Collaborer ───────────────────────────────────────────────────────

    /** Exécute une action sur le projet ouvert puis recharge sa fiche avec un message. */
    private function action(array $r, string $succes): void
    {
        $id = $this->fiche['id'] ?? null;
        if ($id) {
            $this->voir($id, true);
        }
        $this->infoFiche = ($r['ok'] ?? false) ? $succes : ($r['message'] ?? 'Une erreur est survenue.');
    }

    public function suivre(): void
    {
        $r = Api::post("/projects/{$this->fiche['id']}/suivre", [], Api::token());
        $this->action($r, ($r['suivi'] ?? false) ? 'Vous suivez ce projet : vous serez notifié(e) de ses avancées.' : 'Vous ne suivez plus ce projet.');
    }

    public function ouvrirRejoindre(): void
    {
        $this->rejoindreOuvert = ! $this->rejoindreOuvert;
        $this->messageRejoindre = '';
        $this->roleRejoindre = '';
    }

    public function rejoindre(): void
    {
        $r = Api::post("/projects/{$this->fiche['id']}/equipe/rejoindre", [
            'message' => trim($this->messageRejoindre), 'role' => trim($this->roleRejoindre),
        ], Api::token());
        if ($r['ok'] ?? false) {
            $this->rejoindreOuvert = false;
        }
        $this->action($r, ($r['statut'] ?? '') === 'membre' ? "Vous faites maintenant partie de l'équipe !" : "Demande envoyée : l'équipe du projet vous répondra.");
    }

    public function accepterLien(int $lienId): void
    {
        $r = Api::post("/projects/{$this->fiche['id']}/equipe/{$lienId}/accepter", [], Api::token());
        $this->action($r, $this->fiche['relation'] === 'invite' ? "Bienvenue dans l'équipe !" : "Le membre a rejoint l'équipe et il est prévenu.");
    }

    public function retirerLien(int $lienId, string $succes = 'C\'est fait.'): void
    {
        $r = Api::delete("/projects/{$this->fiche['id']}/equipe/{$lienId}", Api::token());
        $this->action($r, $succes);
    }

    public function declinerInvitation(): void
    {
        $this->retirerLien((int) $this->fiche['mon_lien'], 'Invitation déclinée : le porteur est prévenu.');
    }

    public function quitterEquipe(): void
    {
        $this->retirerLien((int) $this->fiche['mon_lien'], "Vous avez quitté l'équipe.");
    }

    public function inviter(int $userId): void
    {
        $r = Api::post("/projects/{$this->fiche['id']}/equipe/inviter", ['user_id' => $userId, 'role' => trim($this->roleInvite)], Api::token());
        if ($r['ok'] ?? false) {
            $this->rechercheCandidat = '';
            $this->roleInvite = '';
        }
        $this->action($r, ($r['statut'] ?? '') === 'membre' ? "Il avait demandé à vous rejoindre : il fait maintenant partie de l'équipe." : 'Invitation envoyée : le membre est notifié.');
    }

    public function publierAvancee(): void
    {
        $r = Api::post("/projects/{$this->fiche['id']}/avancees", ['body' => trim($this->texteAvancee)], Api::token());
        if ($r['ok'] ?? false) {
            $this->texteAvancee = '';
        }
        $n = (int) ($r['notifies'] ?? 0);
        $this->action($r, 'Nouvelle publiée'.($n ? " : {$n} membre".($n > 1 ? 's' : '').' prévenu'.($n > 1 ? 's' : '') : '').'.');
    }

    public function supprimerAvancee(int $avanceeId): void
    {
        $r = Api::delete("/projects/{$this->fiche['id']}/avancees/{$avanceeId}", Api::token());
        $this->action($r, 'Nouvelle supprimée.');
    }

    // ── Formulaire ───────────────────────────────────────────────────────

    public function openForm(): void
    {
        $this->reset(['editingId', 'statutEdition', 'title', 'accroche', 'groupId', 'ville', 'description', 'probleme', 'solution', 'cible', 'impact', 'besoins', 'lien']);
        $this->stade = 'idee';
        $this->membersCount = 1;
        $this->clearMedia();
        $this->resetValidation();
        $this->message = $this->erreur = null;
        $this->fiche = null;
        $this->focus = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $r = Api::get("/projects/{$id}", [], Api::token());
        $p = $r['project'] ?? null;
        if (! $p || ! ($p['mine'] ?? false)) {
            return;
        }
        $this->editingId = $id;
        $this->statutEdition = $p['statut'];
        $this->title = $p['title'];
        $this->accroche = (string) ($p['accroche'] ?? '');
        $this->groupId = (string) ($p['groupe']['id'] ?? '');
        $this->stade = $p['stade'];
        $this->ville = (string) ($p['ville'] ?? '');
        $this->description = $p['description'];
        $this->probleme = (string) ($p['probleme'] ?? '');
        $this->solution = (string) ($p['solution'] ?? '');
        $this->cible = (string) ($p['cible'] ?? '');
        $this->impact = (string) ($p['impact'] ?? '');
        $this->besoins = $p['besoins'] ?? [];
        $this->lien = (string) ($p['lien'] ?? '');
        $this->membersCount = (int) $p['members_count'];
        $this->fillMedia($p['image'] ?? null);
        $this->resetValidation();
        $this->message = $this->erreur = null;
        $this->fiche = null;
        $this->focus = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function enregistrer(): void
    {
        $this->validate();

        $data = [
            'title' => trim($this->title),
            'accroche' => trim($this->accroche) ?: null,
            'group_id' => (int) $this->groupId,
            'stade' => $this->stade,
            'ville' => trim($this->ville) ?: null,
            'description' => trim($this->description),
            'probleme' => trim($this->probleme) ?: null,
            'solution' => trim($this->solution) ?: null,
            'cible' => trim($this->cible) ?: null,
            'impact' => trim($this->impact) ?: null,
            'besoins' => array_values($this->besoins),
            'lien' => trim($this->lien) ?: null,
            'image' => $this->mediaUrl ?: null,
            'members_count' => $this->membersCount,
        ];
        $r = $this->editingId
            ? Api::put("/projects/{$this->editingId}", $data, Api::token())
            : Api::post('/projects', $data, Api::token());

        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? "L'enregistrement a échoué. Réessayez.";

            return;
        }

        $this->message = match (true) {
            ! $this->editingId => "Projet envoyé ! L'équipe REJCC va l'examiner : vous serez notifié(e) de sa décision.",
            (bool) ($r['resoumis'] ?? false) => "Projet complété et renvoyé en évaluation : l'équipe est prévenue.",
            default => 'Modifications enregistrées.',
        };
        $this->erreur = null;
        $this->closeForm();
        $this->onglet = 'mes';
    }

    public function retirer(int $id): void
    {
        $r = Api::post("/projects/{$id}/retirer", [], Api::token());
        $this->fermerFiche();
        $this->message = ($r['ok'] ?? false) ? "Projet retiré : il n'est plus visible des membres." : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Une erreur est survenue.');
    }

    public function supprimer(int $id): void
    {
        Api::delete("/projects/{$id}", Api::token());
        $this->fermerFiche();
        $this->message = 'Projet supprimé.';
    }

    public function render()
    {
        if (! (Api::user()->subscription_active ?? false)) {
            return view('livewire.member.projets', ['locked' => true]);
        }

        $data = Api::get('/projects', [], Api::token());

        $candidats = [];
        if ($this->fiche && mb_strlen(trim($this->rechercheCandidat)) >= 2) {
            $candidats = Api::get("/projects/{$this->fiche['id']}/candidats", ['q' => trim($this->rechercheCandidat)], Api::token())['membres'] ?? [];
        }
        $projets = Collection::make($data['projects'] ?? []);

        return view('livewire.member.projets', [
            'locked' => false,
            'projets' => $projets,
            'mesProjets' => Collection::make($data['mes_projets'] ?? []),
            'mesEquipes' => Collection::make($data['mes_equipes'] ?? []),
            'suivis' => $projets->filter(fn ($p) => $p['suivi'] ?? false)->values(),
            'candidats' => $candidats,
            'categories' => $data['categories'] ?? [],
            'stades' => $data['stades'] ?? [],
            'listeBesoins' => $data['besoins'] ?? [],
        ]);
    }
}
