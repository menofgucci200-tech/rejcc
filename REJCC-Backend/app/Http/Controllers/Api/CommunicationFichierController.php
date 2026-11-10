<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunicationFichier;
use App\Support\OutilsCommunication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Boîte à outils de marque du département Communication : identité, charte,
 * logos, gabarits… Les fichiers sont déposés par le dashboard sur le disque
 * privé partagé (dossier marque/) ; l'API n'enregistre que leurs
 * informations et contrôle l'accès (rubrique « communication »).
 */
class CommunicationFichierController extends Controller
{
    private function payload(CommunicationFichier $f, $user): array
    {
        return [
            ...$f->only(['id', 'titre', 'description', 'categorie', 'fichier_nom', 'lien', 'mime', 'octets', 'telechargements']),
            'categorie_label' => OutilsCommunication::CATEGORIES_FICHIERS[$f->categorie] ?? 'Autres',
            'a_fichier' => $f->fichier !== null,
            'extension' => $f->fichier_nom ? (strtoupper(pathinfo($f->fichier_nom, PATHINFO_EXTENSION)) ?: null) : null,
            'taille' => OutilsCommunication::taille($f->octets),
            'auteur' => $f->auteur ? trim($f->auteur->prenom.' '.$f->auteur->nom) : null,
            'modifiable' => OutilsCommunication::peutModifier($user, $f->auteur_id),
            'created_at' => $f->created_at?->toIso8601String(),
            'updated_at' => $f->updated_at?->toIso8601String(),
        ];
    }

    /** GET /admin/communication/fichiers?categorie=&q= */
    public function index(Request $request)
    {
        $categorie = (string) $request->query('categorie', '');
        $q = trim((string) $request->query('q', ''));
        $fichiers = CommunicationFichier::with('auteur')
            ->when(isset(OutilsCommunication::CATEGORIES_FICHIERS[$categorie]), fn ($r) => $r->where('categorie', $categorie))
            ->when($q !== '', fn ($r) => $r->where(fn ($w) => $w->where('titre', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%")->orWhere('fichier_nom', 'like', "%{$q}%")))
            ->orderByDesc('updated_at')->get();

        return response()->json([
            'ok' => true,
            'fichiers' => $fichiers->map(fn ($f) => $this->payload($f, $request->user()))->values(),
            'categories' => OutilsCommunication::CATEGORIES_FICHIERS,
            'compteurs' => CommunicationFichier::selectRaw('categorie, count(*) as n')->groupBy('categorie')->pluck('n', 'categorie'),
            'taille_max_ko' => OutilsCommunication::TAILLE_MAX_KO,
        ]);
    }

    public function store(Request $request)
    {
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $f = CommunicationFichier::create($d + ['auteur_id' => $request->user()->id]);

        return response()->json(['ok' => true, 'fichier' => $this->payload($f->load('auteur'), $request->user())], 201);
    }

    public function update(Request $request, int $id)
    {
        $f = CommunicationFichier::find($id);
        if (! $f) {
            return response()->json(['ok' => false, 'message' => 'Document introuvable.'], 404);
        }
        if (! OutilsCommunication::peutModifier($request->user(), $f->auteur_id)) {
            return response()->json(['ok' => false, 'message' => OutilsCommunication::MESSAGE_TESTEUR], 403);
        }
        $d = $this->valider($request);
        if ($d instanceof \Illuminate\Http\JsonResponse) {
            return $d;
        }
        $ancien = $f->fichier;
        $f->fill($d)->save();

        return response()->json([
            'ok' => true,
            // Fichier remplacé : le dashboard supprime l'ancien du stockage.
            'ancien_fichier' => $ancien !== $f->fichier ? $ancien : null,
            'fichier' => $this->payload($f->load('auteur'), $request->user()),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $f = CommunicationFichier::find($id);
        if (! $f) {
            return response()->json(['ok' => false, 'message' => 'Document introuvable.'], 404);
        }
        if (! OutilsCommunication::peutModifier($request->user(), $f->auteur_id)) {
            return response()->json(['ok' => false, 'message' => OutilsCommunication::MESSAGE_TESTEUR], 403);
        }
        $chemin = $f->fichier;
        $f->delete();

        return response()->json(['ok' => true, 'fichier' => $chemin]);
    }

    /** GET /admin/communication/fichiers/{id}/acces?action=telechargement : informations pour servir le fichier privé. */
    public function acces(Request $request, int $id)
    {
        $f = CommunicationFichier::find($id);
        if (! $f) {
            return response()->json(['ok' => false, 'message' => 'Document introuvable.'], 404);
        }
        if ($request->query('action') === 'telechargement') {
            $f->increment('telechargements');
        }

        return response()->json(['ok' => true, 'fichier' => ['chemin' => $f->fichier, 'nom' => $f->fichier_nom, 'mime' => $f->mime, 'lien' => $f->lien]]);
    }

    private function valider(Request $request)
    {
        $v = Validator::make($request->all(), [
            'titre' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'categorie' => ['required', Rule::in(array_keys(OutilsCommunication::CATEGORIES_FICHIERS))],
            // Chemin relatif sur le disque privé partagé, toujours sous marque/.
            // Un fichier (chemin relatif sur le disque privé partagé, toujours sous marque/) ou un lien (gabarit Canva…).
            'fichier' => ['nullable', 'required_without:lien', 'string', 'max:255', 'regex:#^marque/[A-Za-z0-9][A-Za-z0-9._/-]*$#', 'not_regex:#\.\.#'],
            'fichier_nom' => ['nullable', 'required_with:fichier', 'string', 'max:255'],
            'lien' => ['nullable', 'required_without:fichier', 'url:https', 'max:500'],
            'mime' => 'nullable|string|max:120',
            'octets' => 'nullable|integer|min:0|max:'.(OutilsCommunication::TAILLE_MAX_KO * 1024),
        ], [
            'titre.required' => 'Donnez un titre au document.',
            'titre.max' => 'Le titre ne doit pas dépasser 150 caractères.',
            'categorie.required' => 'Choisissez une catégorie.',
            'categorie.in' => 'Catégorie inconnue.',
            'fichier.required_without' => 'Choisissez un fichier à envoyer ou collez un lien.',
            'lien.required_without' => 'Choisissez un fichier à envoyer ou collez un lien.',
            'lien.url' => 'Le lien doit commencer par https://.',
            'lien.max' => 'Le lien est trop long.',
            'fichier.regex' => 'Emplacement de fichier invalide.',
            'fichier.not_regex' => 'Emplacement de fichier invalide.',
            'fichier_nom.required_with' => 'Le nom du fichier est manquant.',
            'octets.max' => 'Le fichier ne doit pas dépasser '.(OutilsCommunication::TAILLE_MAX_KO / 1024).' Mo.',
            'description.max' => 'La description ne doit pas dépasser 2 000 caractères.',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $d = $v->validated();
        $d['titre'] = trim($d['titre']);
        $d['description'] = trim((string) ($d['description'] ?? '')) ?: null;
        // Fichier et lien s'excluent : le fichier l'emporte.
        if (! empty($d['fichier'])) {
            $d['lien'] = null;
        } else {
            $d = ['fichier' => null, 'fichier_nom' => null, 'mime' => null, 'octets' => null] + $d;
        }

        return $d;
    }
}
