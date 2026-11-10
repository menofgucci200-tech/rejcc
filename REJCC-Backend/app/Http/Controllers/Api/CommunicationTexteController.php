<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunicationTexte;
use App\Support\OutilsCommunication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Textes prêts à copier du département Communication : présentations du
 * REJCC, signature d'e-mail, bios, hashtags, messages types par type
 * d'information, mentions de consentement. Pré-remplis par défaut ; la Com
 * les adapte, en ajoute et peut restaurer les textes par défaut supprimés.
 */
class CommunicationTexteController extends Controller
{
    private function payload(CommunicationTexte $t, $user): array
    {
        return [
            ...$t->only(['id', 'cle', 'categorie', 'titre', 'contenu', 'limite', 'ordre']),
            'categorie_label' => OutilsCommunication::CATEGORIES_TEXTES[$t->categorie] ?? $t->categorie,
            'par_defaut' => $t->cle !== null,
            'modifiable' => OutilsCommunication::peutModifier($user, $t->auteur_id),
            'updated_at' => $t->updated_at?->toIso8601String(),
        ];
    }

    /** GET /admin/communication/textes?categorie=&q= */
    public function index(Request $request)
    {
        $categorie = (string) $request->query('categorie', '');
        $q = trim((string) $request->query('q', ''));
        $textes = CommunicationTexte::query()
            ->when(isset(OutilsCommunication::CATEGORIES_TEXTES[$categorie]), fn ($r) => $r->where('categorie', $categorie))
            ->when($q !== '', fn ($r) => $r->where(fn ($w) => $w->where('titre', 'like', "%{$q}%")->orWhere('contenu', 'like', "%{$q}%")))
            ->orderBy('ordre')->orderBy('id')->get();
        $cles = CommunicationTexte::whereNotNull('cle')->pluck('cle')->all();

        return response()->json([
            'ok' => true,
            'textes' => $textes->map(fn ($t) => $this->payload($t, $request->user()))->values(),
            'categories' => OutilsCommunication::CATEGORIES_TEXTES,
            'compteurs' => CommunicationTexte::selectRaw('categorie, count(*) as n')->groupBy('categorie')->pluck('n', 'categorie'),
            'defauts_manquants' => count(array_diff(array_keys(OutilsCommunication::textesParDefaut()), $cles)),
        ]);
    }

    public function store(Request $request)
    {
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $ordre = (int) CommunicationTexte::where('categorie', $d['categorie'])->max('ordre') + 10;
        $t = CommunicationTexte::create($d + ['ordre' => $ordre, 'auteur_id' => $request->user()->id]);

        return response()->json(['ok' => true, 'texte' => $this->payload($t, $request->user())], 201);
    }

    public function update(Request $request, int $id)
    {
        $t = CommunicationTexte::find($id);
        if (! $t) {
            return response()->json(['ok' => false, 'message' => 'Texte introuvable.'], 404);
        }
        if (! OutilsCommunication::peutModifier($request->user(), $t->auteur_id)) {
            return response()->json(['ok' => false, 'message' => OutilsCommunication::MESSAGE_TESTEUR], 403);
        }
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $t->fill($d)->save();

        return response()->json(['ok' => true, 'texte' => $this->payload($t, $request->user())]);
    }

    public function destroy(Request $request, int $id)
    {
        $t = CommunicationTexte::find($id);
        if (! $t) {
            return response()->json(['ok' => false, 'message' => 'Texte introuvable.'], 404);
        }
        if (! OutilsCommunication::peutModifier($request->user(), $t->auteur_id)) {
            return response()->json(['ok' => false, 'message' => OutilsCommunication::MESSAGE_TESTEUR], 403);
        }
        $t->delete();

        return response()->json(['ok' => true]);
    }

    /** POST /admin/communication/textes/restaurer : remet les textes par défaut supprimés (les autres ne bougent pas). */
    public function restaurer(Request $request)
    {
        if ($request->user()->estTesteur()) {
            return response()->json(['ok' => false, 'message' => OutilsCommunication::MESSAGE_TESTEUR], 403);
        }

        return response()->json(['ok' => true, 'ajoutes' => OutilsCommunication::installerTextesParDefaut()]);
    }

    private function valider(Request $request)
    {
        $v = Validator::make($request->all(), [
            'categorie' => ['required', Rule::in(array_keys(OutilsCommunication::CATEGORIES_TEXTES))],
            'titre' => 'required|string|max:150',
            'contenu' => 'required|string|max:10000',
            'limite' => 'nullable|integer|min:10|max:10000',
        ], [
            'categorie.required' => 'Choisissez une catégorie.',
            'categorie.in' => 'Catégorie inconnue.',
            'titre.required' => 'Donnez un titre au texte.',
            'titre.max' => 'Le titre ne doit pas dépasser 150 caractères.',
            'contenu.required' => 'Le texte est vide.',
            'contenu.max' => 'Le texte ne doit pas dépasser 10 000 caractères.',
            'limite.integer' => 'La limite de caractères doit être un nombre.',
            'limite.min' => 'La limite de caractères doit être d\'au moins 10.',
            'limite.max' => 'La limite de caractères ne peut pas dépasser 10 000.',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();
        $d['titre'] = trim($d['titre']);
        $d['contenu'] = rtrim($d['contenu']);
        $d['limite'] = $d['limite'] ?? null;

        return $d;
    }
}
