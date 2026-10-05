<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\MemberReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Administration des groupes sectoriels (section « groupes ») : création,
 * modification (identité, référent, annonce épinglée),
 * ordre d'affichage, membres (retrait) et modération des avis.
 */
class GroupAdminController extends Controller
{
    /** Icônes proposées pour un groupe (noms du composant d'icônes du frontend). */
    public const ICONES = [
        'network', 'sprout', 'cpu', 'megaphone', 'landmark', 'folder-open', 'graduation-cap', 'heart-pulse',
        'building-2', 'factory', 'shopping-bag', 'truck', 'bed-double', 'scissors', 'party-popper', 'leaf',
        'hand-heart', 'wrench', 'store', 'globe', 'smartphone', 'users', 'award', 'gem', 'rocket', 'sparkles',
    ];

    /** GET /admin/groups */
    public function index()
    {
        $groups = Group::with('referent:id,prenom,nom')
            ->withCount([
                'users as membres' => fn ($q) => $q->where('users.is_active', true),
            ])
            ->orderBy('ordre')->orderBy('id')->get()
            ->map(fn (Group $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'slug' => $g->slug,
                'description' => $g->description,
                'icone' => $g->icone ?: 'network',
                'couleur' => $g->couleur ?: '#031D59',
                'messages' => $g->messages()->count(),
                'referent' => $g->referent ? ['id' => $g->referent->id, 'nom' => trim($g->referent->prenom.' '.$g->referent->nom)] : null,
                'annonce' => $g->annonce,
                'annonce_at' => $g->annonce_at?->toIso8601String(),
                'membres' => $g->membres,
                'avis' => MemberReview::where('group_id', $g->id)->count(),
            ]);

        return response()->json([
            'ok' => true,
            'groups' => $groups,
            'icones' => self::ICONES,
            'avis_signales' => MemberReview::whereNotNull('signale_at')->where('masque', false)->count(),
        ]);
    }

    private function regles(?Group $group = null): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120', Rule::unique('groups', 'name')->ignore($group?->id)],
            'description' => 'nullable|string|max:500',
            'icone' => ['nullable', Rule::in(self::ICONES)],
            'couleur' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'referent_id' => 'nullable|integer|exists:users,id',
            'annonce' => 'nullable|string|max:1000',
            'notifier' => 'boolean',
        ];
    }

    private const MESSAGES = [
        'name.required' => 'Le nom du groupe est obligatoire.',
        'name.unique' => 'Un groupe porte déjà ce nom.',
        'couleur.regex' => 'La couleur doit être au format #RRGGBB.',
    ];

    /** POST /admin/groups */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), $this->regles(), self::MESSAGES);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();

        $slug = Str::slug($d['name']);
        $base = $slug;
        for ($i = 2; Group::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        $group = Group::create([
            'name' => trim($d['name']),
            'slug' => $slug,
            'description' => $d['description'] ?? null,
            'icone' => $d['icone'] ?? 'network',
            'couleur' => $d['couleur'] ?? '#031D59',
            'ordre' => (int) Group::max('ordre') + 1,
        ]);

        return response()->json(['ok' => true, 'id' => $group->id], 201);
    }

    /** PUT /admin/groups/{id} */
    public function update(Request $request, int $id)
    {
        $group = Group::find($id);
        if (! $group) {
            return response()->json(['ok' => false, 'message' => 'Groupe introuvable.'], 404);
        }

        $v = Validator::make($request->all(), $this->regles($group), self::MESSAGES);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();

        // Le référent est choisi parmi les membres du groupe.
        $referent = $d['referent_id'] ?? null;
        if ($referent && ! $group->users()->where('users.id', $referent)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Le référent doit être membre du groupe.'], 422);
        }

        $annonce = isset($d['annonce']) ? trim($d['annonce']) ?: null : null;
        $nouvelleAnnonce = $annonce !== null && $annonce !== $group->annonce;

        $group->update([
            'name' => trim($d['name']),
            'description' => $d['description'] ?? null,
            'icone' => $d['icone'] ?? $group->icone,
            'couleur' => $d['couleur'] ?? $group->couleur,
            'referent_id' => $referent,
            'annonce' => $annonce,
            'annonce_at' => $annonce === null ? null : ($nouvelleAnnonce ? now() : $group->annonce_at),
        ]);

        $notifies = 0;
        if ($nouvelleAnnonce && ($d['notifier'] ?? false)) {
            $ids = $group->users()->where('users.is_active', true)->pluck('users.id');
            $maintenant = now();
            MemberNotification::insert($ids->map(fn ($uid) => [
                'user_id' => $uid,
                'type' => 'info',
                'title' => "Annonce du groupe {$group->name}",
                'body' => Str::limit($annonce, 180),
                'link' => "/espace-membre/groupes/{$group->id}",
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ])->all());
            $notifies = $ids->count();
        }

        return response()->json(['ok' => true, 'notifies' => $notifies]);
    }

    /** POST /admin/groups/{id}/move — monter (direction=up) ou descendre un groupe. */
    public function move(Request $request, int $id)
    {
        $groups = Group::orderBy('ordre')->orderBy('id')->get()->values();
        $index = $groups->search(fn ($g) => $g->id === $id);
        if ($index === false) {
            return response()->json(['ok' => false, 'message' => 'Groupe introuvable.'], 404);
        }
        $cible = $request->input('direction') === 'up' ? $index - 1 : $index + 1;
        if ($cible >= 0 && $cible < $groups->count()) {
            $liste = $groups->all();
            [$liste[$index], $liste[$cible]] = [$liste[$cible], $liste[$index]];
            DB::transaction(function () use ($liste) {
                foreach ($liste as $i => $g) {
                    $g->update(['ordre' => $i + 1]);
                }
            });
        }

        return response()->json(['ok' => true]);
    }

    /** DELETE /admin/groups/{id} — les fiches des membres dans ce groupe sont supprimées avec lui. */
    public function destroy(int $id)
    {
        $group = Group::find($id);
        if (! $group) {
            return response()->json(['ok' => false, 'message' => 'Groupe introuvable.'], 404);
        }
        $group->users()->detach();
        $group->delete();

        // Renumérote pour garder un ordre continu (1, 2, 3…).
        Group::orderBy('ordre')->orderBy('id')->get()->values()->each(fn (Group $g, int $i) => $g->update(['ordre' => $i + 1]));

        return response()->json(['ok' => true]);
    }

    /** GET /admin/groups/{id}/members — tous les membres (y compris masqués de l'annuaire ou suspendus). */
    public function members(Request $request, int $id)
    {
        $group = Group::find($id);
        if (! $group) {
            return response()->json(['ok' => false, 'message' => 'Groupe introuvable.'], 404);
        }

        $membres = $group->users()->orderBy('users.prenom')->orderBy('users.nom')
            ->get(['users.id', 'users.prenom', 'users.nom', 'users.email', 'users.telephone', 'users.ville', 'users.photo', 'users.is_active', 'users.preferences'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'nom' => trim($u->prenom.' '.$u->nom),
                'email' => $u->email,
                'telephone' => $u->telephone,
                'ville' => $u->ville,
                'photo' => $u->photo,
                'actif' => (bool) $u->is_active,
                'dans_annuaire' => (bool) ($u->preferencesEffectives()['apparaitre_annuaire'] ?? true),
                'specialite' => $u->pivot->specialite,
                'zone' => $u->pivot->zone,
                'services' => json_decode((string) $u->pivot->services, true) ?: [],
                'rejoint_le' => $u->pivot->created_at?->toIso8601String(),
                'avis' => MemberReview::resume($u->id),
            ]);

        return response()->json(['ok' => true, 'group' => ['id' => $group->id, 'name' => $group->name], 'members' => $membres]);
    }

    /** DELETE /admin/groups/{id}/members/{userId} — retire la fiche d'un membre du groupe (il est prévenu). */
    public function removeMember(Request $request, int $id, int $userId)
    {
        $group = Group::find($id);
        if (! $group || ! $group->users()->where('users.id', $userId)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Membre introuvable dans ce groupe.'], 404);
        }

        $group->users()->detach($userId);
        if ($group->referent_id === $userId) {
            $group->update(['referent_id' => null]);
        }

        $motif = trim((string) $request->input('motif'));
        MemberNotification::create([
            'user_id' => $userId,
            'type' => 'warning',
            'title' => "Fiche retirée du groupe {$group->name}",
            'body' => "L'administration a retiré votre fiche du groupe {$group->name}.".($motif !== '' ? " Motif : {$motif}" : '').' Vous pouvez nous écrire pour toute question.',
            'link' => '/espace-membre/groupes',
        ]);

        return response()->json(['ok' => true]);
    }

    /** GET /admin/avis?filtre=signales|masques|tous */
    public function avis(Request $request)
    {
        $filtre = (string) $request->query('filtre', 'signales');
        $query = MemberReview::with(['reviewer:id,prenom,nom', 'reviewed:id,prenom,nom', 'group:id,name'])->latest('updated_at');
        match ($filtre) {
            'signales' => $query->whereNotNull('signale_at')->where('masque', false),
            'masques' => $query->where('masque', true),
            default => null,
        };

        $page = $query->paginate(30);

        return response()->json([
            'ok' => true,
            'avis' => collect($page->items())->map(fn (MemberReview $r) => [
                'id' => $r->id,
                'auteur' => trim(($r->reviewer->prenom ?? '').' '.($r->reviewer->nom ?? '')),
                'professionnel' => trim(($r->reviewed->prenom ?? '').' '.($r->reviewed->nom ?? '')),
                'groupe' => $r->group?->name,
                'note' => $r->note,
                'commentaire' => $r->commentaire,
                'masque' => $r->masque,
                'signale' => $r->signale_at !== null,
                'motif' => $r->motif_signalement,
                'date' => $r->updated_at?->toIso8601String(),
            ])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
        ]);
    }

    /**
     * PUT /admin/avis/{id} — masquer (masque=true) ou rétablir un avis. Dans
     * les deux cas le signalement est traité. L'auteur d'un avis masqué en
     * est informé.
     */
    public function moderer(Request $request, int $id)
    {
        $avis = MemberReview::with('reviewed:id,prenom,nom')->find($id);
        if (! $avis) {
            return response()->json(['ok' => false, 'message' => 'Avis introuvable.'], 404);
        }

        $masque = $request->boolean('masque');
        $avis->update(['masque' => $masque, 'signale_at' => null, 'signale_par' => null, 'motif_signalement' => null]);

        if ($masque) {
            MemberNotification::create([
                'user_id' => $avis->reviewer_id,
                'type' => 'warning',
                'title' => 'Votre avis a été masqué',
                'body' => 'Votre avis sur '.trim(($avis->reviewed->prenom ?? '').' '.($avis->reviewed->nom ?? '')).' a été masqué par la modération : il ne respecte pas la charte du membre. Vous pouvez le modifier depuis sa fiche.',
                'link' => $avis->group_id ? "/espace-membre/groupes/{$avis->group_id}" : '/espace-membre/groupes',
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /** DELETE /admin/avis/{id} */
    public function supprimerAvis(int $id)
    {
        MemberReview::whereKey($id)->delete();

        return response()->json(['ok' => true]);
    }
}
