<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
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
    public function index(Request $request)
    {
        $mesSpecialites = $request->user()->groups()->pluck('group_user.specialite', 'groups.id');

        $groups = Group::withCount('users')
            ->orderBy('ordre')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'description', 'ordre'])
            ->map(fn (Group $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'slug' => $g->slug,
                'description' => $g->description,
                'members' => $g->users_count,
                'joined' => $mesSpecialites->has($g->id),
                'ma_specialite' => $mesSpecialites->get($g->id),
            ]);

        return response()->json(['ok' => true, 'groups' => $groups]);
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
        ], [
            'specialite.required' => 'Décrivez votre spécialité dans ce domaine pour rejoindre le groupe.',
            'specialite.min' => 'Décrivez votre spécialité en quelques mots de plus (10 caractères minimum).',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $request->user()->groups()->syncWithoutDetaching([
            $group->id => ['specialite' => $validator->validated()['specialite']],
        ]);

        return response()->json(['ok' => true, 'members' => $group->users()->count()]);
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
        $query = User::query()
            ->join('group_user', 'group_user.user_id', '=', 'users.id')
            ->where('group_user.group_id', $group->id)
            ->orderBy('users.prenom')
            ->orderBy('users.nom');

        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('users.prenom', 'like', "%{$q}%")
                    ->orWhere('users.nom', 'like', "%{$q}%")
                    ->orWhere('users.ville', 'like', "%{$q}%")
                    ->orWhere('users.organisation', 'like', "%{$q}%")
                    ->orWhere('group_user.specialite', 'like', "%{$q}%");
            });
        }

        $page = $query->paginate(24, [
            'users.id', 'users.prenom', 'users.nom', 'users.ville', 'users.secteur',
            'users.organisation', 'users.photo', 'group_user.specialite',
        ]);

        $members = collect($page->items())->map(fn (User $u) => [
            'id' => $u->id,
            'prenom' => $u->prenom,
            'nom' => $u->nom,
            'ville' => $u->ville,
            'secteur' => $u->secteur,
            'organisation' => $u->organisation,
            'photo' => $u->photo,
            'specialite' => $u->specialite,
        ]);

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
}
