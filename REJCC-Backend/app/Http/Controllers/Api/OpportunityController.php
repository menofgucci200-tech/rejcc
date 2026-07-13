<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OpportunityController extends Controller
{
    /** Liste des opportunités / annonces, les plus récentes d'abord. */
    public function index(Request $request)
    {
        $opps = Opportunity::with('author:id,prenom,nom')->orderByDesc('created_at')->get();

        $out = $opps->map(fn ($o) => [
            'id' => $o->id,
            'title' => $o->title,
            'description' => $o->description,
            'type' => $o->type,
            'contact' => $o->contact,
            'deadline' => $o->deadline?->toDateString(),
            'author' => $o->author ? trim($o->author->prenom.' '.$o->author->nom) : null,
            'media_url' => $o->media_url,
            'media_name' => $o->media_name,
            'created_at' => $o->created_at,
        ]);

        return response()->json(['ok' => true, 'opportunities' => $out]);
    }

    /** Publication d'une opportunité par un membre. */
    public function store(Request $request)
    {
        $me = $request->user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:160',
            'description' => 'required|string|max:3000',
            'type' => 'required|string|max:40',
            'contact' => 'nullable|string|max:160',
            'deadline' => 'nullable|date',
            'media_url' => 'nullable|url|max:500',
            'media_name' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();
        $data['author_id'] = $me->id;

        $opp = Opportunity::create($data);

        return response()->json(['ok' => true, 'opportunity' => $opp], 201);
    }

    // ------------------------------------------------------------------
    // Administration (modération des annonces)
    // ------------------------------------------------------------------

    public function adminUpdate(Request $request, int $id)
    {
        $opp = Opportunity::find($id);
        if (! $opp) {
            return response()->json(['ok' => false, 'message' => 'Annonce introuvable.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:160',
            'description' => 'required|string|max:3000',
            'type' => 'required|string|max:40',
            'contact' => 'nullable|string|max:160',
            'deadline' => 'nullable|date',
            'media_url' => 'nullable|url|max:500',
            'media_name' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $opp->fill($validator->validated())->save();

        return response()->json(['ok' => true, 'opportunity' => $opp]);
    }

    public function adminDestroy(int $id)
    {
        Opportunity::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }
}
