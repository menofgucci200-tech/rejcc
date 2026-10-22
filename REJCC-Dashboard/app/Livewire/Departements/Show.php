<?php

namespace App\Livewire\Departements;

use App\Support\AdminNav;
use App\Support\AdminSections;
use App\Support\Api;
use App\Support\Espace;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Espace d'un département du Bureau : sa mission, son équipe et l'accès
 * direct aux rubriques qu'il gère. Un compte de département n'ouvre que le
 * sien ; l'administrateur général les ouvre tous.
 */
#[Layout('layouts.admin-light')]
class Show extends Component
{
    #[Locked]
    public string $slug;

    // ── Accès au département (administrateur général) ───────────────────
    /** '' | membre (donner l'accès à un membre existant) | nouveau (créer un compte) */
    public string $ajout = '';

    public string $recherche = '';

    public string $prenom = '';

    public string $nom = '';

    public string $email = '';

    public string $telephone = '';

    public string $motDePasse = '';

    public ?int $rubriquesDe = null;

    /** @var list<string> rubriques cochées pour le compte en cours de modification */
    public array $rubriquesChoisies = [];

    public ?string $message = null;

    public ?string $erreur = null;

    public function mount(string $slug): void
    {
        $user = session('api_user');
        abort_unless(Espace::adminGeneral($user) || Espace::departement($user) === $slug, 403, 'Cet espace appartient à un autre département.');
        $this->slug = $slug;
    }

    private function exigerAdminGeneral(): void
    {
        abort_unless(Espace::adminGeneral(session('api_user')), 403, 'Réservé à l\'administrateur général.');
    }

    public function ouvrirAjout(string $type): void
    {
        $this->exigerAdminGeneral();
        $this->ajout = $this->ajout === $type ? '' : $type;
        $this->reset(['recherche', 'prenom', 'nom', 'email', 'telephone', 'erreur', 'message']);
        // Mot de passe proposé, que l'administrateur transmet à la personne.
        $this->motDePasse = $type === 'nouveau' ? 'Rejcc-'.strtoupper(Str::random(4)).'-'.random_int(1000, 9999) : '';
    }

    /** Donne l'accès au département à un membre existant (son compte reste le même). */
    public function donnerAcces(int $userId, string $nom): void
    {
        $this->exigerAdminGeneral();
        $r = Api::put("/admin/members/{$userId}", ['role' => 'admin', 'bureau_departement' => $this->slug], Api::token());
        $this->terminer($r, "{$nom} a maintenant accès à l'espace du département. Il se connecte sur le dashboard avec son compte habituel.");
    }

    public function creerCompte(): void
    {
        $this->exigerAdminGeneral();
        $this->validate([
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:150',
            'telephone' => ['required', 'regex:/^[0-9]{10}$/'],
            'motDePasse' => 'required|string|min:8',
        ], ['telephone.regex' => 'Le téléphone doit compter 10 chiffres.'], ['motDePasse' => 'mot de passe', 'telephone' => 'téléphone']);
        $r = Api::post('/admin/members', [
            'prenom' => $this->prenom, 'nom' => $this->nom, 'email' => $this->email, 'telephone' => $this->telephone,
            'password' => $this->motDePasse, 'role' => 'admin', 'bureau_departement' => $this->slug,
        ], Api::token());
        $this->terminer($r, "Compte créé pour {$this->prenom} {$this->nom}. Transmettez-lui : dashboard.rejcc.site, {$this->email} et le mot de passe {$this->motDePasse}.");
    }

    public function suspendre(int $userId, bool $actif): void
    {
        $this->exigerAdminGeneral();
        $r = Api::put("/admin/members/{$userId}", ['is_active' => ! $actif], Api::token());
        $this->terminer($r, $actif ? 'Accès suspendu.' : 'Accès réactivé.');
    }

    /** Retire l'accès : la personne redevient simple membre du réseau. */
    public function retirer(int $userId): void
    {
        $this->exigerAdminGeneral();
        $r = Api::put("/admin/members/{$userId}", ['role' => 'member'], Api::token());
        $this->terminer($r, 'Accès retiré : la personne garde son compte de membre.');
    }

    public function modifierRubriques(int $userId, array $actuelles): void
    {
        $this->exigerAdminGeneral();
        $this->rubriquesDe = $this->rubriquesDe === $userId ? null : $userId;
        $this->rubriquesChoisies = $actuelles;
    }

    public function enregistrerRubriques(): void
    {
        $this->exigerAdminGeneral();
        if ($this->rubriquesChoisies === []) {
            $this->erreur = 'Gardez au moins une rubrique, ou retirez l\'accès.';

            return;
        }
        $r = Api::put("/admin/members/{$this->rubriquesDe}", ['permissions' => array_values($this->rubriquesChoisies)], Api::token());
        $this->rubriquesDe = null;
        $this->terminer($r, 'Rubriques mises à jour.');
    }

    private function terminer(array $r, string $succes): void
    {
        if ($r['ok'] ?? false) {
            $this->message = $succes;
            $this->erreur = null;
            $this->ajout = '';
        } else {
            $this->erreur = $r['message'] ?? 'Action impossible.';
        }
    }

    public function render()
    {
        $departement = collect(Api::get('/admin/departements', [], Api::token())['departements'] ?? [])->firstWhere('slug', $this->slug);
        abort_if($departement === null, 404, 'Département introuvable.');

        // Rubriques du menu que ce département gère, dans l'ordre du menu.
        $rubriques = collect(AdminNav::GROUPES)->flatMap(fn ($g) => $g['items'])
            ->filter(fn ($i) => in_array(AdminSections::ROUTES[$i['route']] ?? null, $departement['sections'], true))
            ->filter(fn ($i) => AdminSections::allowedRoute(session('api_user'), $i['route']))
            ->values();

        $adminGeneral = Espace::adminGeneral(session('api_user'));
        $resultats = $adminGeneral && $this->ajout === 'membre' && mb_strlen(trim($this->recherche)) >= 2
            ? collect(Api::get('/admin/members', ['q' => trim($this->recherche)], Api::token())['members'] ?? [])
                ->reject(fn ($m) => ($m['bureau_departement'] ?? null) === $this->slug)->take(8)->values()->all()
            : [];

        // Cockpit du département Formation : chiffres et « À faire ».
        $cockpit = $this->slug === 'formation' && AdminSections::allowed(session('api_user'), 'formations')
            ? Api::get('/admin/formations-live/cockpit', [], Api::token())
            : null;

        return view('livewire.departements.show', [
            'cockpit' => ($cockpit['ok'] ?? false) ? $cockpit : null,
            'resultats' => $resultats,
            'sectionsDisponibles' => AdminSections::SECTIONS,
            'departement' => $departement,
            'rubriques' => $rubriques,
            'pastilles' => AdminNav::pastilles(),
            'adminGeneral' => Espace::adminGeneral(session('api_user')),
        ]);
    }
}
