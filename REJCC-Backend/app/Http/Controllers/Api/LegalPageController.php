<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Pages légales : lecture publique (contenu seulement une fois publié),
 * rédaction et publication depuis l'administration (section Contenu).
 */
class LegalPageController extends Controller
{
    private function publique(LegalPage $p): array
    {
        return [
            'slug' => $p->slug,
            'titre' => $p->titre,
            'resume' => $p->resume,
            'publie' => $p->publie && trim((string) $p->contenu) !== '',
            'contenu' => $p->publie ? $p->contenu : null,
            'version' => $p->publie ? $p->version : null,
            'mise_a_jour' => $p->publie ? $p->publie_at?->toDateString() : null,
        ];
    }

    /** GET /legal-pages — liste (pour les pieds de page). */
    public function index()
    {
        return response()->json(['ok' => true, 'pages' => LegalPage::orderBy('ordre')->get()->map(fn ($p) => [
            'slug' => $p->slug, 'titre' => $p->titre, 'publie' => $p->publie && trim((string) $p->contenu) !== '',
        ])]);
    }

    /** GET /legal-pages/{slug} */
    public function show(string $slug)
    {
        $p = LegalPage::where('slug', $slug)->first();

        return $p
            ? response()->json(['ok' => true, 'page' => $this->publique($p)])
            : response()->json(['ok' => false, 'message' => 'Page introuvable.'], 404);
    }

    /** GET /admin/legal-pages — toutes les pages, brouillons compris. */
    public function adminIndex()
    {
        return response()->json(['ok' => true, 'pages' => LegalPage::orderBy('ordre')->get()->map(fn ($p) => [
            'slug' => $p->slug, 'titre' => $p->titre, 'resume' => $p->resume, 'contenu' => $p->contenu,
            'publie' => $p->publie, 'version' => $p->version, 'publie_at' => $p->publie_at?->toIso8601String(),
            'modifie_at' => $p->updated_at?->toIso8601String(),
        ])]);
    }

    /**
     * PUT /admin/legal-pages/{slug} — enregistre le texte ; « publier » met en
     * ligne une nouvelle version (1.0, 1.1, …) datée du jour.
     */
    public function update(Request $request, string $slug)
    {
        $p = LegalPage::where('slug', $slug)->first();
        if (! $p) {
            return response()->json(['ok' => false, 'message' => 'Page introuvable.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|min:3|max:150',
            'resume' => 'nullable|string|max:300',
            'contenu' => 'nullable|string|max:100000',
            'publier' => 'boolean',
            'depublier' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $p->fill($request->only(['titre', 'resume', 'contenu']));

        if ($request->boolean('publier')) {
            if (trim((string) $p->contenu) === '') {
                return response()->json(['ok' => false, 'message' => 'Rédigez le contenu de la page avant de la publier.'], 422);
            }
            [$maj, $min] = array_map('intval', explode('.', $p->version ?: '0.9'));
            $p->version = $p->version ? "{$maj}.".($min + 1) : '1.0';
            $p->publie = true;
            $p->publie_at = now();
        } elseif ($request->boolean('depublier')) {
            $p->publie = false;
        }
        $p->save();

        return response()->json(['ok' => true, 'version' => $p->version, 'publie' => $p->publie]);
    }
}
