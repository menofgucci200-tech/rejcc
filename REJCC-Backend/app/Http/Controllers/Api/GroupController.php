<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\MemberReview;
use App\Models\User;
use App\Support\RechercheMots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Groupes sectoriels : les membres rejoignent librement les pôles de leur
 * choix (adhésion multiple possible), en décrivant leur spécialité dans le
 * domaine (ex. « Plombier spécialisé en dépannage sanitaire »). Le
 * trombinoscope (liste des membres du groupe) est réservé aux abonnés à
 * jour — cf. middleware `sub.active` sur la route dans routes/api.php.
 */
class GroupController extends Controller
{
    /** Membres affichés dans un groupe : comptes actifs qui n'ont pas choisi de se retirer de l'annuaire. */
    private function visibles($query)
    {
        return $query->where('users.is_active', true)
            ->where(fn ($w) => $w->whereNull('users.preferences')
                ->orWhereNull('users.preferences->apparaitre_annuaire')
                ->orWhere('users.preferences->apparaitre_annuaire', true));
    }

    public function index(Request $request)
    {
        $mesFiches = $request->user()->groups()->get()->keyBy('id');

        $groups = Group::withCount(['users' => fn ($q) => $q->where('users.is_active', true)])
            ->orderBy('ordre')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'description', 'ordre'])
            ->map(fn (Group $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'slug' => $g->slug,
                'description' => $g->description,
                'members' => $g->users_count,
                'joined' => $mesFiches->has($g->id),
                'ma_specialite' => $mesFiches->get($g->id)?->pivot->specialite,
                'ma_fiche' => $mesFiches->has($g->id) ? $this->fiche($mesFiches->get($g->id)->pivot) : null,
            ]);

        return response()->json(['ok' => true, 'groups' => $groups]);
    }

    /** Fiche professionnelle (colonnes du pivot) sous forme de tableau. */
    private function fiche(object $pivot): array
    {
        $services = $pivot->services ?? null;

        return [
            'specialite' => $pivot->specialite,
            'services' => is_array($services) ? $services : (json_decode((string) $services, true) ?: []),
            'zone' => $pivot->zone,
            'disponibilites' => $pivot->disponibilites,
            'telephone_visible' => (bool) $pivot->telephone_visible,
        ];
    }

    /** Rejoint un groupe (ou met à jour sa spécialité s'il en est déjà membre). */
    public function join(Request $request, int $id)
    {
        $group = Group::find($id);
        if (! $group) {
            return response()->json(['ok' => false, 'message' => 'Groupe introuvable.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'specialite' => 'required|string|min:10|max:600',
            'services' => 'nullable|array|max:10',
            'services.*' => 'string|min:2|max:80',
            'zone' => 'nullable|string|max:255',
            'disponibilites' => 'nullable|string|max:255',
            'telephone_visible' => 'boolean',
        ], [
            'services.max' => 'Indiquez au plus 10 services.',
            'specialite.required' => 'Décrivez votre spécialité dans ce domaine pour rejoindre le groupe.',
            'specialite.min' => 'Décrivez votre spécialité en quelques mots de plus (10 caractères minimum).',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $d = $validator->validated();
        $request->user()->groups()->syncWithoutDetaching([
            $group->id => [
                'specialite' => trim($d['specialite']),
                'services' => json_encode(collect($d['services'] ?? [])->map(fn ($s) => trim($s))->filter()->unique()->values()->all()),
                'zone' => isset($d['zone']) ? trim($d['zone']) ?: null : null,
                'disponibilites' => isset($d['disponibilites']) ? trim($d['disponibilites']) ?: null : null,
                'telephone_visible' => (bool) ($d['telephone_visible'] ?? false),
            ],
        ]);

        return response()->json(['ok' => true, 'members' => $group->users()->where('users.is_active', true)->count()]);
    }

    public function leave(Request $request, int $id)
    {
        $group = Group::find($id);
        if (! $group) {
            return response()->json(['ok' => false, 'message' => 'Groupe introuvable.'], 404);
        }

        $request->user()->groups()->detach($group->id);

        return response()->json(['ok' => true, 'members' => $group->users()->count()]);
    }

    /** Champs texte interrogés par la recherche dans les groupes. */
    private const CHAMPS_RECHERCHE = [
        'users.prenom', 'users.nom', 'users.ville', 'users.organisation', 'users.titre',
        'group_user.specialite', 'group_user.zone',
    ];

    /** Colonnes de la carte d'un membre + note moyenne et nombre d'avis visibles. */
    private function avecNotes($query)
    {
        // select() puis addSelect() : paginate($colonnes) écraserait les sous-requêtes.
        return $query->select([
            'users.id', 'users.prenom', 'users.nom', 'users.ville', 'users.secteur',
            'users.organisation', 'users.photo', 'users.role', 'users.titre', 'users.created_at',
            'group_user.specialite', 'group_user.services', 'group_user.zone', 'group_user.disponibilites',
        ])->addSelect([
            'note_moyenne' => MemberReview::selectRaw('avg(note)')->whereColumn('reviewed_id', 'users.id')->where('masque', false),
            'nb_avis' => MemberReview::selectRaw('count(*)')->whereColumn('reviewed_id', 'users.id')->where('masque', false),
        ]);
    }

    /** Tri : par nom (défaut), par note (les mieux notés d'abord) ou par arrivée dans le groupe. */
    private function trier($query, string $tri): void
    {
        if ($tri === 'note') {
            $query->orderByRaw('(select avg(note) from member_reviews where reviewed_id = users.id and masque = ?) is null', [false])
                ->orderByDesc(MemberReview::selectRaw('avg(note)')->whereColumn('reviewed_id', 'users.id')->where('masque', false))
                ->orderByDesc(MemberReview::selectRaw('count(*)')->whereColumn('reviewed_id', 'users.id')->where('masque', false));
        } elseif ($tri === 'recents') {
            $query->orderByDesc('group_user.created_at');
        }
        $query->orderBy('users.prenom')->orderBy('users.nom');
    }

    /** Carte d'un membre dans un groupe (trombinoscope, recherche). */
    private function carte(User $u): array
    {
        return [
            'id' => $u->id,
            'prenom' => $u->prenom,
            'nom' => $u->nom,
            'ville' => $u->ville,
            'secteur' => $u->secteur,
            'organisation' => $u->organisation,
            'photo' => $u->photo,
            'role' => $u->role,
            'titre' => $u->titre,
            'nouveau' => $u->created_at?->gt(now()->subDays(30)) ?? false,
            'specialite' => $u->specialite,
            'services' => array_slice(json_decode((string) $u->services, true) ?: [], 0, 4),
            'zone' => $u->zone,
            'disponibilites' => $u->disponibilites,
            'note_moyenne' => $u->note_moyenne !== null ? round((float) $u->note_moyenne, 1) : null,
            'nb_avis' => (int) $u->nb_avis,
        ];
    }

    /**
     * GET /groups/recherche?q= — « Je cherche… » : trouve les professionnels
     * dans tous les groupes à la fois (« plombier Cocody »). Les abonnés
     * voient les fiches, les autres seulement le nombre de résultats par
     * groupe (pour leur montrer ce que l'abonnement débloque).
     */
    public function recherche(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (RechercheMots::mots($q) === []) {
            return response()->json(['ok' => true, 'q' => $q, 'total' => 0, 'par_groupe' => [], 'members' => [], 'verrouille' => false]);
        }

        $base = fn () => tap($this->visibles(User::query()
            ->join('group_user', 'group_user.user_id', '=', 'users.id')
            ->join('groups', 'groups.id', '=', 'group_user.group_id')),
            fn ($query) => RechercheMots::appliquer($query, $q, [...self::CHAMPS_RECHERCHE, 'groups.name'], ['group_user.services']));

        $parGroupe = $base()->selectRaw('groups.id, groups.name, count(*) as n')
            ->groupBy('groups.id', 'groups.name')->orderByDesc('n')->get()
            ->map(fn ($g) => ['id' => $g->id, 'nom' => $g->name, 'nombre' => (int) $g->n])->values();

        $abonne = $request->user()->hasActiveSubscription();
        $members = [];
        $meta = null;
        if ($abonne) {
            $query = $base();
            $this->trier($query, (string) $request->query('tri', 'note'));
            $page = $this->avecNotes($query)->addSelect(['groups.id as groupe_id', 'groups.name as groupe_nom'])->paginate(24);
            $members = collect($page->items())->map(fn (User $u) => $this->carte($u) + [
                'groupe' => ['id' => $u->groupe_id, 'nom' => $u->groupe_nom],
            ])->values();
            $meta = ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()];
        }

        return response()->json([
            'ok' => true,
            'q' => $q,
            'total' => $parGroupe->sum('nombre'),
            'par_groupe' => $parGroupe,
            'members' => $members,
            'meta' => $meta,
            'verrouille' => ! $abonne,
        ]);
    }

    /** Trombinoscope du groupe : liste des membres avec leur spécialité (réservé aux abonnés). */
    public function members(Request $request, int $id)
    {
        $group = Group::find($id);
        if (! $group) {
            return response()->json(['ok' => false, 'message' => 'Groupe introuvable.'], 404);
        }

        $q = trim((string) $request->query('q', ''));

        // Jointure explicite (plutôt que $group->users()->paginate()) : la
        // pagination sur une relation BelongsToMany ne réhydrate pas
        // toujours proprement les colonnes du pivot.
        $query = $this->visibles(User::query()
            ->join('group_user', 'group_user.user_id', '=', 'users.id')
            ->where('group_user.group_id', $group->id));

        RechercheMots::appliquer($query, $q, self::CHAMPS_RECHERCHE, ['group_user.services']);

        $this->trier($query, (string) $request->query('tri', 'nom'));

        $page = $this->avecNotes($query)->paginate(24);

        $members = collect($page->items())->map(fn (User $u) => $this->carte($u));

        return response()->json([
            'ok' => true,
            'group' => ['id' => $group->id, 'name' => $group->name, 'description' => $group->description],
            'members' => $members,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
        ]);
    }

    /**
     * GET /groups/{id}/members/{userId} — fiche professionnelle complète d'un
     * membre du groupe : profil, spécialité, services, zone, disponibilités,
     * téléphone (si le membre l'a autorisé) et ses autres groupes.
     */
    public function fichePro(Request $request, int $id, int $userId)
    {
        $group = Group::find($id);
        $membre = $group ? $this->visibles(User::query())->whereKey($userId)->first() : null;
        $lien = $membre?->groups()->where('groups.id', $id)->first();
        if (! $group || ! $membre || ! $lien) {
            return response()->json(['ok' => false, 'message' => 'Membre introuvable dans ce groupe.'], 404);
        }

        $fiche = $this->fiche($lien->pivot);
        $profil = \App\Support\MemberProfile::payload($membre);
        if ($fiche['telephone_visible'] && $membre->telephone) {
            $profil['telephone'] = $membre->telephone;
        }

        return response()->json(['ok' => true, 'fiche' => [
            'groupe' => ['id' => $group->id, 'nom' => $group->name],
            'membre' => $profil,
            'pro' => $fiche,
            'avis' => MemberReviewController::avisDe($membre->id, $request->user()->id),
            'autres_groupes' => $membre->groups()->where('groups.id', '!=', $id)->orderBy('ordre')->get()
                ->map(fn ($g) => ['id' => $g->id, 'nom' => $g->name, 'specialite' => $g->pivot->specialite])->values(),
        ]]);
    }
}
