<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\Project;
use App\Models\ProjectFollow;
use App\Models\ProjectMember;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Support\RechercheMots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Projets du réseau : un membre propose son projet, l'équipe l'évalue
 * (valider, demander des précisions, refuser avec motif) ; les projets
 * validés sont visibles des membres, les autres seulement de leur porteur.
 */
class ProjectController extends Controller
{
    private function categories()
    {
        return Group::orderBy('ordre')->orderBy('id')->get(['id', 'name', 'icone', 'couleur'])
            ->map(fn (Group $g) => ['id' => $g->id, 'nom' => $g->name, 'icone' => $g->icone ?: 'network', 'couleur' => $g->couleur ?: '#031D59'])->values();
    }

    private function referentiels(): array
    {
        return ['categories' => $this->categories(), 'stades' => Project::STADES, 'besoins' => Project::BESOINS, 'statuts' => Project::STATUTS];
    }

    /** Données d'un projet ; $complet ajoute la fiche détaillée. */
    private function payload(Project $p, ?User $moi, bool $complet = false): array
    {
        $u = $p->porteur;
        $data = [
            'id' => $p->id,
            'title' => $p->title,
            'accroche' => $p->accroche,
            'description' => $p->description,
            'statut' => $p->statut,
            'statut_label' => Project::STATUTS[$p->statut] ?? $p->statut,
            'stade' => $p->stade,
            'stade_label' => Project::STADES[$p->stade] ?? $p->stade,
            'besoins' => array_values(array_intersect(array_keys(Project::BESOINS), $p->besoins ?? [])),
            'groupe' => $p->groupe ? ['id' => $p->groupe->id, 'nom' => $p->groupe->name, 'couleur' => $p->groupe->couleur ?: '#031D59', 'icone' => $p->groupe->icone ?: 'network'] : null,
            'ville' => $p->ville,
            'image' => $p->image,
            'members_count' => (int) $p->members_count,
            'a_la_une' => (bool) $p->a_la_une,
            'public_ok' => (bool) $p->public_ok,
            'equipe_taille' => 1 + $p->equipe->where('statut', 'membre')->count(),
            'porteur' => $u ? ['id' => $u->id, 'prenom' => $u->prenom, 'nom' => $u->nom, 'photo' => $u->photo, 'role' => $u->role, 'titre' => $u->titre] : null,
            'mine' => $moi && $p->user_id === $moi->id,
            'soumis_at' => $p->soumis_at?->toIso8601String(),
            'decide_at' => $p->decide_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
        ];
        if ($moi) {
            $lien = $p->equipe->firstWhere('user_id', $moi->id);
            $data['relation'] = $p->user_id === $moi->id ? 'porteur' : $lien?->statut; // porteur | membre | invite | demande | null
            $data['mon_role'] = $lien?->role;
            $data['mon_lien'] = $lien?->id;
        }
        if ($complet) {
            $estEquipe = $moi && in_array($data['relation'] ?? null, ['porteur', 'membre'], true);
            $data += [
                'equipe' => $p->equipe->where('statut', 'membre')->filter(fn ($m) => $m->user)->map(fn (ProjectMember $m) => [
                    'id' => $m->id, 'role' => $m->role,
                    'membre' => $m->user->only(['id', 'prenom', 'nom', 'photo', 'role', 'titre']),
                ])->values(),
                // Invitations et demandes en attente : visibles de l'équipe.
                'en_attente' => $estEquipe ? $p->equipe->whereIn('statut', ['invite', 'demande'])->filter(fn ($m) => $m->user)->map(fn (ProjectMember $m) => [
                    'id' => $m->id, 'statut' => $m->statut, 'role' => $m->role, 'message' => $m->message,
                    'membre' => $m->user->only(['id', 'prenom', 'nom', 'photo', 'role', 'titre']),
                ])->values() : [],
                'suivi' => $moi && $p->suivis()->where('user_id', $moi->id)->exists(),
                'nb_suivis' => $p->suivis()->count(),
                'avancees' => $p->avancees()->with('auteur:id,prenom,nom,photo,role')->limit(10)->get()->map(fn (ProjectUpdate $u) => [
                    'id' => $u->id, 'body' => $u->body, 'image' => $u->image, 'date' => $u->created_at->toIso8601String(),
                    'auteur' => $u->auteur?->only(['id', 'prenom', 'nom', 'photo', 'role']),
                    'supprimable' => $moi && ($u->user_id === $moi->id || $p->user_id === $moi->id),
                ])->values(),
                'peut_publier' => $estEquipe && $p->statut === 'valide',
                'probleme' => $p->probleme,
                'solution' => $p->solution,
                'cible' => $p->cible,
                'impact' => $p->impact,
                'lien' => $p->lien,
                'vues' => (int) $p->vues,
            ];
        }
        // Motif de refus / précisions demandées : réservé au porteur (et à l'admin).
        if ($moi && ($p->user_id === $moi->id || $moi->role === 'admin')) {
            $data['motif'] = $p->motif;
            $data['modifiable'] = $p->statut !== 'retire';
        }

        return $data;
    }

    private function regles(): array
    {
        return [
            'title' => 'required|string|min:4|max:160',
            'accroche' => 'nullable|string|max:200',
            'description' => 'required|string|min:20|max:3000',
            'probleme' => 'nullable|string|max:2000',
            'solution' => 'nullable|string|max:2000',
            'cible' => 'nullable|string|max:1000',
            'impact' => 'nullable|string|max:1000',
            'group_id' => 'required|integer|exists:groups,id',
            'stade' => ['required', Rule::in(array_keys(Project::STADES))],
            'ville' => 'nullable|string|max:80',
            'image' => 'nullable|url|max:500',
            'lien' => 'nullable|url|max:500',
            'besoins' => 'nullable|array',
            'besoins.*' => [Rule::in(array_keys(Project::BESOINS))],
            'members_count' => 'nullable|integer|min:1|max:500',
            'public_ok' => 'nullable|boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'title.required' => 'Donnez un nom à votre projet.',
            'title.min' => 'Le nom du projet est trop court (4 caractères minimum).',
            'description.required' => 'Décrivez votre projet en quelques phrases.',
            'description.min' => 'La description est trop courte : présentez votre projet en au moins 20 caractères.',
            'group_id.required' => 'Choisissez le secteur de votre projet.',
            'group_id.exists' => 'Choisissez le secteur de votre projet.',
            'image.url' => "L'image doit être une adresse web complète.",
            'lien.url' => 'Le lien doit être une adresse web complète (https://…).',
        ];
    }

    private function valider(Request $request): array|\Illuminate\Http\JsonResponse
    {
        $v = Validator::make($request->all(), $this->regles(), $this->messages());
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();
        $d['besoins'] = array_values(array_unique($d['besoins'] ?? []));
        $d['members_count'] ??= 1;
        $d['public_ok'] = (bool) ($d['public_ok'] ?? false);

        return $d;
    }

    private function notifier(Project $p, string $titre, string $texte): void
    {
        if ($p->user_id) {
            MemberNotification::create([
                'user_id' => $p->user_id, 'type' => 'info', 'title' => $titre, 'body' => $texte,
                'link' => "/espace-membre/projets?projet={$p->id}",
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Site public (projets validés dont le porteur a donné son accord)
    // ------------------------------------------------------------------

    private function payloadPublic(Project $p, bool $complet = false): array
    {
        $data = [
            'id' => $p->id,
            'title' => $p->title,
            'accroche' => $p->accroche,
            'description' => $p->description,
            'stade' => Project::STADES[$p->stade] ?? $p->stade,
            'groupe' => $p->groupe ? ['nom' => $p->groupe->name, 'couleur' => $p->groupe->couleur ?: '#031D59', 'icone' => $p->groupe->icone ?: 'network'] : null,
            'ville' => $p->ville,
            'image' => $p->image,
            'a_la_une' => (bool) $p->a_la_une,
            // Prénom seulement : pas de nom complet ni de coordonnées sur la vitrine.
            'porteur' => $p->porteur?->prenom,
            'besoins' => array_values(array_map(fn ($b) => Project::BESOINS[$b] ?? $b, $p->besoins ?? [])),
            'equipe_taille' => 1 + $p->equipe()->where('statut', 'membre')->count(),
        ];
        if ($complet) {
            $data += ['probleme' => $p->probleme, 'solution' => $p->solution, 'cible' => $p->cible, 'impact' => $p->impact, 'lien' => $p->lien];
        }

        return $data;
    }

    /** GET /public-projects — vitrine : projets à la une d'abord. */
    public function publicIndex()
    {
        $projets = Project::with(['porteur:id,prenom', 'groupe:id,name,couleur,icone'])
            ->where('statut', 'valide')->where('public_ok', true)
            ->orderByDesc('a_la_une')->orderByDesc('decide_at')->get();

        return response()->json(['ok' => true, 'projects' => $projets->map(fn ($p) => $this->payloadPublic($p))->values()]);
    }

    /** GET /public-projects/{id} — fiche publique. */
    public function publicShow(int $id)
    {
        $p = Project::with(['porteur:id,prenom', 'groupe:id,name,couleur,icone'])
            ->where('statut', 'valide')->where('public_ok', true)->find($id);
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Projet introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'project' => $this->payloadPublic($p, true)]);
    }

    // ------------------------------------------------------------------
    // Espace membre
    // ------------------------------------------------------------------

    /**
     * GET /projects?q=&groupe=&stade=&besoin=&ville=&tri= — projets validés
     * du réseau (filtrés côté serveur) + mes projets, mes équipes, mes suivis.
     */
    public function index(Request $request)
    {
        $moi = $request->user();
        $query = Project::select('projects.*')
            ->leftJoin('users', 'users.id', '=', 'projects.user_id')
            ->leftJoin('groups', 'groups.id', '=', 'projects.group_id')
            ->with(['porteur:id,prenom,nom,photo,role,titre', 'groupe:id,name,couleur,icone', 'equipe.user:id,prenom,nom,photo,role,titre'])
            ->withCount('suivis')
            ->where('projects.statut', 'valide');

        RechercheMots::appliquer($query, (string) $request->query('q', ''), [
            'projects.title', 'projects.accroche', 'projects.description', 'projects.solution', 'projects.ville',
            'users.prenom', 'users.nom', 'groups.name',
        ]);
        if ($groupe = (int) $request->query('groupe')) {
            $query->where('projects.group_id', $groupe);
        }
        if (isset(Project::STADES[$stade = (string) $request->query('stade')])) {
            $query->where('projects.stade', $stade);
        }
        if (isset(Project::BESOINS[$besoin = (string) $request->query('besoin')])) {
            $query->where('projects.besoins', 'like', '%"'.$besoin.'"%');
        }
        if ($ville = trim((string) $request->query('ville', ''))) {
            $query->where('projects.ville', $ville);
        }
        match ($request->query('tri')) {
            'suivis' => $query->orderByDesc('suivis_count')->orderByDesc('projects.decide_at'),
            'vues' => $query->orderByDesc('projects.vues'),
            default => $query->orderByDesc('projects.a_la_une')->orderByDesc('projects.decide_at')->orderByDesc('projects.created_at'),
        };
        $projets = $query->get();
        $villes = Project::where('statut', 'valide')->whereNotNull('ville')->where('ville', '!=', '')
            ->distinct()->orderBy('ville')->pluck('ville');

        $mes = Project::with(['porteur:id,prenom,nom,photo,role,titre', 'groupe:id,name,couleur,icone', 'equipe.user:id,prenom,nom,photo,role,titre'])
            ->where('user_id', $moi->id)->orderByDesc('created_at')->get();

        $equipes = Project::with(['porteur:id,prenom,nom,photo,role,titre', 'groupe:id,name,couleur,icone', 'equipe.user:id,prenom,nom,photo,role,titre'])
            ->where('statut', '!=', 'retire')
            ->whereHas('equipe', fn ($e) => $e->where('user_id', $moi->id)->whereIn('statut', ['membre', 'invite', 'demande']))
            ->orderByDesc('updated_at')->get();
        $suivis = ProjectFollow::where('user_id', $moi->id)->pluck('project_id')->all();

        return response()->json([
            'ok' => true,
            'projects' => $projets->map(fn ($p) => $this->payload($p, $moi) + ['suivi' => in_array($p->id, $suivis, true)])->values(),
            'mes_projets' => $mes->map(fn ($p) => $this->payload($p, $moi))->values(),
            'mes_equipes' => $equipes->map(fn ($p) => $this->payload($p, $moi))->values(),
            'suivis' => $suivis,
            'mes_suivis' => Project::with(['porteur:id,prenom,nom,photo,role,titre', 'groupe:id,name,couleur,icone', 'equipe.user:id,prenom,nom,photo,role,titre'])
                ->where('statut', 'valide')->whereIn('id', $suivis)->get()
                ->map(fn ($p) => $this->payload($p, $moi) + ['suivi' => true])->values(),
            'villes' => $villes,
            'total_valides' => Project::where('statut', 'valide')->count(),
        ] + $this->referentiels());
    }

    /**
     * GET /projects-apercu — ce que contient la rubrique, pour les membres non
     * abonnés : chiffres, secteurs, besoins et quelques titres (sans porteur).
     */
    public function apercu()
    {
        $valides = Project::where('statut', 'valide');
        $besoins = [];
        foreach ((clone $valides)->pluck('besoins') as $liste) {
            foreach ($liste ?? [] as $b) {
                if (isset(Project::BESOINS[$b])) {
                    $besoins[Project::BESOINS[$b]] = ($besoins[Project::BESOINS[$b]] ?? 0) + 1;
                }
            }
        }
        arsort($besoins);

        return response()->json(['ok' => true, 'apercu' => [
            'projets' => (clone $valides)->count(),
            'equipiers' => ProjectMember::where('statut', 'membre')->whereIn('project_id', (clone $valides)->select('id'))->count(),
            'secteurs' => (clone $valides)->join('groups', 'groups.id', '=', 'projects.group_id')
                ->selectRaw('groups.name as nom, count(*) as nombre')->groupBy('groups.name')->orderByDesc('nombre')->limit(6)->pluck('nombre', 'nom'),
            'besoins' => $besoins,
            'exemples' => (clone $valides)->with('groupe:id,name,couleur,icone')->latest('decide_at')->limit(6)->get()
                ->map(fn (Project $p) => ['title' => $p->title, 'stade' => Project::STADES[$p->stade] ?? $p->stade,
                    'groupe' => $p->groupe ? ['nom' => $p->groupe->name, 'couleur' => $p->groupe->couleur ?: '#031D59', 'icone' => $p->groupe->icone ?: 'network'] : null])->values(),
        ]]);
    }

    /** GET /projects/{id} — fiche complète (projet validé, ou le mien). */
    public function show(Request $request, int $id)
    {
        $moi = $request->user();
        $p = Project::with(['porteur:id,prenom,nom,photo,role,titre', 'groupe:id,name,couleur,icone', 'equipe.user:id,prenom,nom,photo,role,titre'])->find($id);
        if (! $p || ! $p->estVisiblePar($moi) || ($p->statut === 'retire' && $p->user_id !== $moi->id)) {
            return response()->json(['ok' => false, 'message' => "Ce projet n'est pas (ou plus) visible."], 404);
        }
        if ($p->user_id !== $moi->id) {
            $p->increment('vues');
        }

        return response()->json(['ok' => true, 'project' => $this->payload($p, $moi, true)] + $this->referentiels());
    }

    /** POST /projects — un membre propose un projet (entre en évaluation). */
    public function store(Request $request)
    {
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $moi = $request->user();
        if (Project::where('user_id', $moi->id)->where('statut', 'evaluation')->count() >= 3) {
            return response()->json(['ok' => false, 'message' => "Vous avez déjà 3 projets en cours d'évaluation : attendez la réponse de l'équipe avant d'en proposer un autre."], 422);
        }

        $project = Project::create($d + ['user_id' => $moi->id, 'statut' => 'evaluation', 'soumis_at' => now()]);

        return response()->json(['ok' => true, 'project' => $this->payload($project->load('groupe', 'porteur', 'equipe.user'), $moi, true)], 201);
    }

    /**
     * PUT /projects/{id} — le porteur modifie son projet. À compléter ou
     * refusé : la modification le renvoie en évaluation.
     */
    public function update(Request $request, int $id)
    {
        $moi = $request->user();
        $p = Project::where('user_id', $moi->id)->find($id);
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Projet introuvable.'], 404);
        }
        if ($p->statut === 'retire') {
            return response()->json(['ok' => false, 'message' => 'Ce projet est retiré : il ne peut plus être modifié.'], 422);
        }
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $resoumis = in_array($p->statut, ['a_completer', 'refuse'], true);
        if ($resoumis) {
            $d += ['statut' => 'evaluation', 'soumis_at' => now()];
        }
        $p->update($d);

        return response()->json(['ok' => true, 'resoumis' => $resoumis, 'project' => $this->payload($p->fresh(['groupe', 'porteur', 'equipe.user']), $moi, true)]);
    }

    /** POST /projects/{id}/retirer — le porteur retire son projet (plus visible des membres). */
    public function retirer(Request $request, int $id)
    {
        $p = Project::where('user_id', $request->user()->id)->find($id);
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Projet introuvable.'], 404);
        }
        $p->update(['statut' => 'retire']);

        return response()->json(['ok' => true]);
    }

    /** DELETE /projects/{id} — le porteur supprime son projet. */
    public function destroy(Request $request, int $id)
    {
        Project::where('user_id', $request->user()->id)->whereKey($id)->delete();

        return response()->json(['ok' => true]);
    }

    // ------------------------------------------------------------------
    // Collaborer : équipe, suivre, avancées
    // ------------------------------------------------------------------

    private function notif(int $userId, Project $p, string $titre, string $texte): void
    {
        MemberNotification::create([
            'user_id' => $userId, 'type' => 'info', 'title' => $titre, 'body' => $texte,
            'link' => "/espace-membre/projets?projet={$p->id}",
        ]);
    }

    private function nomDe(User $u): string
    {
        return trim($u->prenom.' '.$u->nom);
    }

    /** GET /projects/{id}/candidats?q= — membres de l'annuaire à inviter dans l'équipe. */
    public function candidats(Request $request, int $id)
    {
        $moi = $request->user();
        $p = Project::find($id);
        if (! $p || ! $p->estDeLEquipe($moi)) {
            return response()->json(['ok' => false, 'message' => 'Projet introuvable.'], 404);
        }
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'membres' => []]);
        }
        $deja = $p->equipe()->pluck('user_id')->push($p->user_id)->all();
        $membres = User::where('is_active', true)->whereIn('role', ['member', 'mentor'])->whereNotIn('id', $deja)
            ->where(fn ($w) => $w->whereNull('preferences')->orWhereNull('preferences->apparaitre_annuaire')->orWhere('preferences->apparaitre_annuaire', true))
            // Chaque mot doit se retrouver dans le prénom ou le nom (« awa tra » trouve Awa Traoré).
            ->where(function ($w) use ($q) {
                foreach (preg_split('/\s+/', $q) as $mot) {
                    $w->where(fn ($x) => $x->where('prenom', 'like', "%{$mot}%")->orWhere('nom', 'like', "%{$mot}%"));
                }
            })
            ->orderBy('prenom')->limit(8)->get(['id', 'prenom', 'nom', 'photo', 'role', 'titre', 'ville']);

        return response()->json(['ok' => true, 'membres' => $membres]);
    }

    /** POST /projects/{id}/equipe/inviter — l'équipe invite un membre du réseau. */
    public function inviter(Request $request, int $id)
    {
        $moi = $request->user();
        $p = Project::find($id);
        if (! $p || ! $p->estDeLEquipe($moi)) {
            return response()->json(['ok' => false, 'message' => 'Projet introuvable.'], 404);
        }
        if ($p->statut === 'retire' || $p->statut === 'refuse') {
            return response()->json(['ok' => false, 'message' => "Ce projet n'accepte plus de nouveaux membres."], 422);
        }
        $invite = User::where('is_active', true)->find((int) $request->input('user_id'));
        if (! $invite || $invite->id === $p->user_id) {
            return response()->json(['ok' => false, 'message' => 'Choisissez un membre du réseau.'], 422);
        }
        $role = mb_substr(trim((string) $request->input('role')), 0, 80) ?: null;
        $lien = ProjectMember::firstWhere(['project_id' => $p->id, 'user_id' => $invite->id]);
        if ($lien?->statut === 'membre') {
            return response()->json(['ok' => false, 'message' => $this->nomDe($invite).' fait déjà partie de l\'équipe.'], 422);
        }
        if ($lien?->statut === 'demande') {
            // Il avait demandé à rejoindre : l'invitation vaut acceptation.
            $lien->update(['statut' => 'membre', 'role' => $role ?? $lien->role]);
            $this->notif($invite->id, $p, "Bienvenue dans l'équipe : {$p->title}", 'Votre demande pour rejoindre le projet est acceptée.');

            return response()->json(['ok' => true, 'statut' => 'membre']);
        }
        ProjectMember::updateOrCreate(['project_id' => $p->id, 'user_id' => $invite->id], [
            'statut' => 'invite', 'role' => $role, 'message' => mb_substr(trim((string) $request->input('message')), 0, 500) ?: null,
        ]);
        $this->notif($invite->id, $p, "Invitation : rejoindre le projet {$p->title}",
            $this->nomDe($moi)." vous invite à rejoindre l'équipe".($role ? " comme {$role}" : '').'. Ouvrez le projet pour accepter ou décliner.');

        return response()->json(['ok' => true, 'statut' => 'invite']);
    }

    /** POST /projects/{id}/equipe/rejoindre — un membre demande à rejoindre l'équipe. */
    public function rejoindre(Request $request, int $id)
    {
        $moi = $request->user();
        $p = Project::where('statut', 'valide')->find($id);
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Projet introuvable.'], 404);
        }
        if ($p->user_id === $moi->id) {
            return response()->json(['ok' => false, 'message' => "C'est votre projet."], 422);
        }
        $lien = ProjectMember::firstWhere(['project_id' => $p->id, 'user_id' => $moi->id]);
        if ($lien?->statut === 'invite') {
            return $this->accepterLien($p, $lien, $moi);
        }
        if ($lien) {
            return response()->json(['ok' => false, 'message' => $lien->statut === 'membre' ? "Vous faites déjà partie de l'équipe." : 'Votre demande est déjà envoyée.'], 422);
        }
        $message = mb_substr(trim((string) $request->input('message')), 0, 500);
        if (mb_strlen($message) < 10) {
            return response()->json(['ok' => false, 'message' => 'Présentez-vous en quelques mots : ce que vous pouvez apporter au projet.'], 422);
        }
        ProjectMember::create(['project_id' => $p->id, 'user_id' => $moi->id, 'statut' => 'demande',
            'role' => mb_substr(trim((string) $request->input('role')), 0, 80) ?: null, 'message' => $message]);
        $this->notif($p->user_id, $p, "Demande pour rejoindre : {$p->title}", $this->nomDe($moi)." souhaite rejoindre votre équipe : « {$message} »");

        return response()->json(['ok' => true, 'statut' => 'demande']);
    }

    private function accepterLien(Project $p, ProjectMember $lien, User $par)
    {
        $lien->update(['statut' => 'membre']);
        $u = $lien->user;
        if ($par->id === $lien->user_id) {
            // L'invité accepte : le porteur est prévenu.
            $this->notif($p->user_id, $p, "Nouvelle recrue : {$p->title}", $this->nomDe($u).' a rejoint votre équipe.');
        } else {
            $this->notif($lien->user_id, $p, "Bienvenue dans l'équipe : {$p->title}", 'Votre demande pour rejoindre le projet est acceptée.');
        }

        return response()->json(['ok' => true, 'statut' => 'membre']);
    }

    /**
     * POST /projects/{id}/equipe/{lien}/accepter — l'invité accepte son
     * invitation, ou l'équipe accepte une demande.
     */
    public function accepter(Request $request, int $id, int $lienId)
    {
        $moi = $request->user();
        $p = Project::find($id);
        $lien = $p ? ProjectMember::with('user')->where('project_id', $p->id)->find($lienId) : null;
        if (! $lien) {
            return response()->json(['ok' => false, 'message' => 'Invitation introuvable.'], 404);
        }
        $autorise = ($lien->statut === 'invite' && $lien->user_id === $moi->id)
            || ($lien->statut === 'demande' && $p->estDeLEquipe($moi));
        if (! $autorise) {
            return response()->json(['ok' => false, 'message' => 'Action impossible.'], 403);
        }

        return $this->accepterLien($p, $lien, $moi);
    }

    /**
     * DELETE /projects/{id}/equipe/{lien} — décliner une invitation, refuser
     * une demande, retirer un membre (équipe) ou quitter l'équipe (soi-même).
     */
    public function retirerMembre(Request $request, int $id, int $lienId)
    {
        $moi = $request->user();
        $p = Project::find($id);
        $lien = $p ? ProjectMember::with('user')->where('project_id', $p->id)->find($lienId) : null;
        if (! $lien) {
            return response()->json(['ok' => false, 'message' => 'Introuvable.'], 404);
        }
        $soiMeme = $lien->user_id === $moi->id;
        if (! $soiMeme && $p->user_id !== $moi->id && ! ($lien->statut === 'demande' && $p->estDeLEquipe($moi))) {
            return response()->json(['ok' => false, 'message' => 'Action impossible.'], 403);
        }
        $statut = $lien->statut;
        $lien->delete();
        if ($soiMeme) {
            $this->notif($p->user_id, $p, $statut === 'invite' ? "Invitation déclinée : {$p->title}" : "Départ de l'équipe : {$p->title}",
                $this->nomDe($moi).($statut === 'invite' ? " a décliné votre invitation." : ($statut === 'demande' ? ' a retiré sa demande.' : " a quitté l'équipe.")));
        } elseif ($statut === 'demande') {
            $this->notif($lien->user_id, $p, "Demande non retenue : {$p->title}", "L'équipe du projet n'a pas retenu votre demande pour le moment. Merci pour votre proposition !");
        } elseif ($statut === 'membre') {
            $this->notif($lien->user_id, $p, "Équipe du projet : {$p->title}", "Vous ne faites plus partie de l'équipe de ce projet.");
        }

        return response()->json(['ok' => true]);
    }

    /** POST /projects/{id}/suivre — suivre / ne plus suivre un projet validé. */
    public function suivre(Request $request, int $id)
    {
        $moi = $request->user();
        $p = Project::where('statut', 'valide')->find($id);
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Projet introuvable.'], 404);
        }
        $suivi = ProjectFollow::where('project_id', $p->id)->where('user_id', $moi->id)->first();
        $suivi ? $suivi->delete() : ProjectFollow::create(['project_id' => $p->id, 'user_id' => $moi->id]);

        return response()->json(['ok' => true, 'suivi' => ! $suivi, 'nb_suivis' => $p->suivis()->count()]);
    }

    /** POST /projects/{id}/avancees — l'équipe publie une avancée ; abonnés au projet et équipe sont notifiés. */
    public function publier(Request $request, int $id)
    {
        $moi = $request->user();
        $p = Project::where('statut', 'valide')->find($id);
        if (! $p || ! $p->estDeLEquipe($moi)) {
            return response()->json(['ok' => false, 'message' => "Seule l'équipe d'un projet validé peut publier ses avancées."], 403);
        }
        $v = Validator::make($request->all(), ['body' => 'required|string|min:5|max:2000', 'image' => 'nullable|url|max:500'],
            ['body.required' => 'Écrivez votre nouvelle.', 'body.min' => 'Votre nouvelle est trop courte.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $u = ProjectUpdate::create($v->validated() + ['project_id' => $p->id, 'user_id' => $moi->id]);

        $extrait = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $u->body), 110);
        $destinataires = ProjectFollow::where('project_id', $p->id)->pluck('user_id')
            ->merge($p->equipe()->where('statut', 'membre')->pluck('user_id'))->push($p->user_id)
            ->unique()->reject(fn ($uid) => $uid === $moi->id);
        $now = now();
        MemberNotification::insert($destinataires->map(fn ($uid) => [
            'user_id' => $uid, 'type' => 'info', 'title' => "Du nouveau sur le projet {$p->title}", 'body' => $extrait,
            'link' => "/espace-membre/projets?projet={$p->id}", 'created_at' => $now, 'updated_at' => $now,
        ])->values()->all());

        return response()->json(['ok' => true, 'notifies' => $destinataires->count()]);
    }

    /** DELETE /projects/{id}/avancees/{avancee} — par son auteur ou le porteur. */
    public function supprimerAvancee(Request $request, int $id, int $avancee)
    {
        $moi = $request->user();
        $p = Project::find($id);
        $u = $p ? ProjectUpdate::where('project_id', $p->id)->find($avancee) : null;
        if (! $u || ($u->user_id !== $moi->id && $p->user_id !== $moi->id)) {
            return response()->json(['ok' => false, 'message' => 'Action impossible.'], 403);
        }
        $u->delete();

        return response()->json(['ok' => true]);
    }

    // ------------------------------------------------------------------
    // Administration
    // ------------------------------------------------------------------

    public function adminIndex(Request $request)
    {
        $moi = $request->user();
        $statut = (string) $request->query('statut', '');
        $q = trim((string) $request->query('q', ''));

        $query = Project::with(['porteur:id,prenom,nom,photo,role,titre,email,telephone', 'groupe:id,name,couleur,icone', 'equipe.user:id,prenom,nom,photo,role,titre'])
            ->when($statut !== '' && isset(Project::STATUTS[$statut]), fn ($w) => $w->where('statut', $statut))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%")
                ->orWhere('ville', 'like', "%{$q}%")
                ->orWhereHas('porteur', fn ($u) => $u->where('prenom', 'like', "%{$q}%")->orWhere('nom', 'like', "%{$q}%"))))
            // Les projets à traiter d'abord, du plus ancien au plus récent.
            ->orderByRaw("CASE statut WHEN 'evaluation' THEN 0 WHEN 'a_completer' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN statut = 'evaluation' THEN soumis_at END ASC")
            ->orderByDesc('updated_at');

        $projets = $query->get()->map(fn (Project $p) => $this->payload($p, $moi, true) + [
            'porteur_email' => $p->porteur?->email,
            'porteur_telephone' => $p->porteur?->telephone,
        ]);
        $compteurs = Project::selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut');

        return response()->json(['ok' => true, 'projects' => $projets->values(), 'compteurs' => $compteurs] + $this->referentiels());
    }

    /** PUT /admin/projects/{id} — correction par l'équipe (le porteur est prévenu). */
    public function adminUpdate(Request $request, int $id)
    {
        $project = Project::find($id);
        if (! $project) {
            return response()->json(['ok' => false, 'message' => 'Projet introuvable.'], 404);
        }
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $project->update($d);
        $note = trim((string) $request->input('note'));
        $this->notifier($project, "Projet mis à jour par l'équipe : {$project->title}",
            "L'équipe REJCC a apporté des corrections à la fiche de votre projet.".($note !== '' ? " Note : {$note}" : ''));

        return response()->json(['ok' => true, 'project' => $this->payload($project->fresh(['groupe', 'porteur', 'equipe.user']), $request->user(), true)]);
    }

    /**
     * POST /admin/projects/{id}/decision — valider (avec stade), demander des
     * précisions ou refuser ; le motif est transmis au porteur.
     */
    public function decision(Request $request, int $id)
    {
        $p = Project::find($id);
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Projet introuvable.'], 404);
        }
        $v = Validator::make($request->all(), [
            'decision' => 'required|in:valider,completer,refuser',
            'motif' => 'nullable|string|max:1000|required_if:decision,completer,refuser',
            'stade' => ['nullable', Rule::in(array_keys(Project::STADES))],
        ], [
            'motif.required_if' => 'Indiquez au porteur ce qui doit être complété ou la raison du refus.',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();
        $motif = trim((string) ($d['motif'] ?? '')) ?: null;

        match ($d['decision']) {
            'valider' => $p->update(['statut' => 'valide', 'stade' => $d['stade'] ?? $p->stade, 'motif' => null, 'decide_at' => now()]),
            'completer' => $p->update(['statut' => 'a_completer', 'motif' => $motif, 'decide_at' => now()]),
            'refuser' => $p->update(['statut' => 'refuse', 'motif' => $motif, 'decide_at' => now()]),
        };

        [$titre, $texte] = match ($d['decision']) {
            'valider' => ["Projet validé : {$p->title}", 'Félicitations ! Votre projet est désormais visible par les membres du réseau, qui peuvent vous contacter pour contribuer.'.($motif ? " Message de l'équipe : {$motif}" : '')],
            'completer' => ["Projet à compléter : {$p->title}", "L'équipe a besoin de précisions avant de valider votre projet : {$motif} Modifiez votre fiche pour la renvoyer en évaluation."],
            'refuser' => ["Projet non retenu : {$p->title}", "Votre projet n'a pas été retenu. Motif : {$motif} Vous pouvez le retravailler et le soumettre à nouveau."],
        };
        $this->notifier($p, $titre, $texte);

        return response()->json(['ok' => true, 'project' => $this->payload($p->fresh(['groupe', 'porteur', 'equipe.user']), $request->user(), true)]);
    }

    /** POST /admin/projects/{id}/une — met un projet validé à la une (ou l'en retire). */
    public function une(int $id)
    {
        $p = Project::where('statut', 'valide')->find($id);
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Seul un projet validé peut être mis à la une.'], 422);
        }
        $p->update(['a_la_une' => ! $p->a_la_une]);
        if ($p->a_la_une) {
            $this->notifier($p, "Votre projet est à la une : {$p->title}",
                "L'équipe REJCC met votre projet en avant auprès des membres".($p->public_ok ? ' et sur le site public du réseau.' : '.'));
        }

        return response()->json(['ok' => true, 'a_la_une' => $p->a_la_une]);
    }

    public function adminDestroy(int $id)
    {
        Project::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }
}
