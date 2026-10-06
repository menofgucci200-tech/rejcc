<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\Project;
use App\Models\User;
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
            'porteur' => $u ? ['id' => $u->id, 'prenom' => $u->prenom, 'nom' => $u->nom, 'photo' => $u->photo, 'role' => $u->role, 'titre' => $u->titre] : null,
            'mine' => $moi && $p->user_id === $moi->id,
            'soumis_at' => $p->soumis_at?->toIso8601String(),
            'decide_at' => $p->decide_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
        ];
        if ($complet) {
            $data += [
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
    // Espace membre
    // ------------------------------------------------------------------

    /** GET /projects — projets validés du réseau + mes projets (tous statuts). */
    public function index(Request $request)
    {
        $moi = $request->user();
        $projets = Project::with(['porteur:id,prenom,nom,photo,role,titre', 'groupe:id,name,couleur,icone'])
            ->where('statut', 'valide')
            ->orderByDesc('decide_at')->orderByDesc('created_at')->get();
        $mes = Project::with(['porteur:id,prenom,nom,photo,role,titre', 'groupe:id,name,couleur,icone'])
            ->where('user_id', $moi->id)->orderByDesc('created_at')->get();

        return response()->json([
            'ok' => true,
            'projects' => $projets->map(fn ($p) => $this->payload($p, $moi))->values(),
            'mes_projets' => $mes->map(fn ($p) => $this->payload($p, $moi))->values(),
        ] + $this->referentiels());
    }

    /** GET /projects/{id} — fiche complète (projet validé, ou le mien). */
    public function show(Request $request, int $id)
    {
        $moi = $request->user();
        $p = Project::with(['porteur:id,prenom,nom,photo,role,titre', 'groupe:id,name,couleur,icone'])->find($id);
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

        return response()->json(['ok' => true, 'project' => $this->payload($project->load('groupe'), $moi, true)], 201);
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

        return response()->json(['ok' => true, 'resoumis' => $resoumis, 'project' => $this->payload($p->fresh(['groupe', 'porteur']), $moi, true)]);
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
    // Administration
    // ------------------------------------------------------------------

    public function adminIndex(Request $request)
    {
        $moi = $request->user();
        $statut = (string) $request->query('statut', '');
        $q = trim((string) $request->query('q', ''));

        $query = Project::with(['porteur:id,prenom,nom,photo,role,titre,email,telephone', 'groupe:id,name,couleur,icone'])
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

        return response()->json(['ok' => true, 'project' => $this->payload($project->fresh(['groupe', 'porteur']), $request->user(), true)]);
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

        return response()->json(['ok' => true, 'project' => $this->payload($p->fresh(['groupe', 'porteur']), $request->user(), true)]);
    }

    public function adminDestroy(int $id)
    {
        Project::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }
}
