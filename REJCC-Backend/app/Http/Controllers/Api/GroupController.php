<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

/**
 * Groupes sectoriels : les membres rejoignent librement les pôles de leur
 * choix (adhésion multiple possible, ex. pour suivre plusieurs formations
 * en parallèle).
 */
class GroupController extends Controller
{
    public function index(Request $request)
    {
        $mesGroupes = $request->user()->groups()->pluck('groups.id')->all();

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
                'joined' => in_array($g->id, $mesGroupes, true),
            ]);

        return response()->json(['ok' => true, 'groups' => $groups]);
    }

    public function join(Request $request, int $id)
    {
        $group = Group::find($id);
        if (! $group) {
            return response()->json(['ok' => false, 'message' => 'Groupe introuvable.'], 404);
        }

        $request->user()->groups()->syncWithoutDetaching([$group->id]);

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
}
