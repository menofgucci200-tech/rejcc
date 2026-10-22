<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Retours des comptes de test (campagne de test sur le site réel) : bugs,
 * suggestions, besoins des départements. Réservé aux comptes de test.
 */
class SuggestionController extends Controller
{
    private function testeur(Request $request): void
    {
        abort_unless($request->user()->estTesteur(), 403, 'Réservé aux comptes de test.');
    }

    /** GET /suggestions : ceux de la personne connectée. */
    public function mesSuggestions(Request $request)
    {
        $this->testeur($request);

        return response()->json([
            'ok' => true,
            'categories' => Suggestion::CATEGORIES,
            'suggestions' => Suggestion::where('user_id', $request->user()->id)->latest()->get()
                ->map(fn (Suggestion $s) => $this->format($s)),
        ]);
    }

    /** POST /suggestions */
    public function store(Request $request)
    {
        $this->testeur($request);
        $v = Validator::make($request->all(), [
            'categorie' => ['required', Rule::in(array_keys(Suggestion::CATEGORIES))],
            'message' => 'required|string|min:10|max:3000',
            'page' => 'nullable|string|max:500',
        ], [
            'message.min' => 'Décrivez ce que vous avez constaté en quelques mots (10 caractères au moins).',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }

        $user = $request->user();
        $s = Suggestion::create($v->validated() + [
            'user_id' => $user->id,
            'departement' => $user->departement,
            // Navigateur du testeur (transmis par le site) : utile pour reproduire un bug.
            'navigateur' => mb_substr((string) ($request->header('X-Client-Agent') ?: $request->userAgent()), 0, 255) ?: null,
            'statut' => 'nouvelle',
        ]);

        return response()->json(['ok' => true, 'suggestion' => $this->format($s)], 201);
    }

    /** GET /admin/suggestions?departement=&statut= */
    public function index(Request $request)
    {
        $q = Suggestion::with('user:id,prenom,nom,email')->latest();
        if ($d = $request->query('departement')) {
            $d === '_aucun' ? $q->whereNull('departement') : $q->where('departement', $d);
        }
        if (($st = $request->query('statut')) && isset(Suggestion::STATUTS[$st])) {
            $q->where('statut', $st);
        }

        return response()->json([
            'ok' => true,
            'categories' => Suggestion::CATEGORIES,
            'statuts' => Suggestion::STATUTS,
            'departements' => Suggestion::query()->selectRaw('departement, count(*) as nombre')
                ->groupBy('departement')->orderBy('departement')->get(),
            'suggestions' => $q->get()->map(fn (Suggestion $s) => $this->format($s)),
        ]);
    }

    /** PUT /admin/suggestions/{id} : statut et réponse de l'équipe. */
    public function update(Request $request, int $id)
    {
        $s = Suggestion::findOrFail($id);
        $v = Validator::make($request->all(), [
            'statut' => ['sometimes', Rule::in(array_keys(Suggestion::STATUTS))],
            'reponse' => 'sometimes|nullable|string|max:2000',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $s->update($v->validated());

        return response()->json(['ok' => true, 'suggestion' => $this->format($s->fresh('user'))]);
    }

    private function format(Suggestion $s): array
    {
        return [
            'id' => $s->id,
            'departement' => $s->departement,
            'categorie' => $s->categorie,
            'categorie_label' => Suggestion::CATEGORIES[$s->categorie] ?? $s->categorie,
            'page' => $s->page,
            'navigateur' => $s->navigateur,
            'message' => $s->message,
            'statut' => $s->statut,
            'statut_label' => Suggestion::STATUTS[$s->statut] ?? $s->statut,
            'reponse' => $s->reponse,
            'auteur' => $s->relationLoaded('user') && $s->user ? trim($s->user->prenom.' '.$s->user->nom) : null,
            'created_at' => $s->created_at?->toIso8601String(),
        ];
    }
}
