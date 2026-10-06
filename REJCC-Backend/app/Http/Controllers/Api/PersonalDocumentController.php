<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * « Mes documents personnels » : coffre-fort privé du membre. Le fichier est
 * stocké (chiffré) par le site dans le dossier du membre ; l'API garde la
 * fiche et contrôle l'accès : le membre seul, ou l'équipe REJCC si le membre
 * a choisi de partager la pièce.
 */
class PersonalDocumentController extends Controller
{
    public function index(Request $request)
    {
        $docs = PersonalDocument::where('user_id', $request->user()->id)->orderBy('type')->latest()->get();

        return response()->json([
            'ok' => true,
            'documents' => $docs->map(fn ($d) => $d->payload())->values(),
            'types' => PersonalDocument::TYPES,
            'max' => PersonalDocument::MAX,
        ]);
    }

    private function regles(bool $creation): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(PersonalDocument::TYPES))],
            'titre' => 'nullable|string|max:150|required_if:type,autre',
            'fichier' => ($creation ? 'required' : 'nullable').'|string|max:500',
            'fichier_nom' => 'required_with:fichier|nullable|string|max:200',
            'mime' => 'nullable|string|max:120',
            'octets' => 'nullable|integer|min:0',
            'delivre_le' => 'nullable|date|before_or_equal:today',
            'expire_le' => 'nullable|date|after_or_equal:delivre_le',
            'partage' => 'boolean',
        ];
    }

    private function messages(): array
    {
        return [
            'type.required' => 'Choisissez le type de document.',
            'titre.required_if' => 'Donnez un nom à ce document.',
            'fichier.required' => 'Joignez le fichier.',
            'delivre_le.before_or_equal' => 'La date de délivrance ne peut pas être dans le futur.',
            'expire_le.after_or_equal' => "La date d'expiration doit suivre la date de délivrance.",
        ];
    }

    /** Le fichier doit être dans le dossier du membre (jamais celui d'un autre). */
    private function cheminValide(?string $fichier, User $moi): bool
    {
        return $fichier === null || $fichier === ''
            || (str_starts_with($fichier, 'personnels/'.$moi->id.'/') && ! str_contains($fichier, '..'));
    }

    public function store(Request $request)
    {
        $moi = $request->user();
        $v = Validator::make($request->all(), $this->regles(true), $this->messages());
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        if (! $this->cheminValide($request->input('fichier'), $moi)) {
            return response()->json(['ok' => false, 'message' => 'Fichier invalide.'], 422);
        }
        if (PersonalDocument::where('user_id', $moi->id)->count() >= PersonalDocument::MAX) {
            return response()->json(['ok' => false, 'message' => 'Vous avez atteint la limite de '.PersonalDocument::MAX.' documents. Supprimez-en un pour en ajouter un autre.'], 422);
        }
        $data = $v->validated();
        $d = PersonalDocument::create($data + ['user_id' => $moi->id, 'partage_at' => ! empty($data['partage']) ? now() : null]);

        return response()->json(['ok' => true, 'document' => $d->fresh()->payload()], 201);
    }

    public function update(Request $request, int $id)
    {
        $moi = $request->user();
        $d = PersonalDocument::where('user_id', $moi->id)->find($id);
        if (! $d) {
            return response()->json(['ok' => false, 'message' => 'Document introuvable.'], 404);
        }
        $v = Validator::make($request->all(), $this->regles(false), $this->messages());
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        if (! $this->cheminValide($request->input('fichier'), $moi)) {
            return response()->json(['ok' => false, 'message' => 'Fichier invalide.'], 422);
        }
        $data = array_filter($v->validated(), fn ($val, $k) => ! in_array($k, ['fichier', 'fichier_nom', 'mime', 'octets'], true) || $val !== null, ARRAY_FILTER_USE_BOTH);
        $ancien = null;
        if (! empty($data['fichier']) && $data['fichier'] !== $d->fichier) {
            $ancien = $d->fichier;
        }
        // Nouvelle date d'expiration : un nouveau rappel sera envoyé.
        if (array_key_exists('expire_le', $data) && (string) $d->expire_le?->toDateString() !== (string) $data['expire_le']) {
            $data['rappel_at'] = null;
        }
        if (array_key_exists('partage', $data) && (bool) $data['partage'] !== $d->partage) {
            $data['partage_at'] = $data['partage'] ? now() : null;
        }
        $d->update($data);

        return response()->json(['ok' => true, 'document' => $d->fresh()->payload(), 'ancien_fichier' => $ancien]);
    }

    public function destroy(Request $request, int $id)
    {
        $d = PersonalDocument::where('user_id', $request->user()->id)->find($id);
        if (! $d) {
            return response()->json(['ok' => false, 'message' => 'Document introuvable.'], 404);
        }
        $fichier = $d->fichier;
        $d->delete();

        return response()->json(['ok' => true, 'fichier' => $fichier]);
    }

    /** GET /mes-documents/{id}/acces — le membre seul. */
    public function acces(Request $request, int $id)
    {
        $d = PersonalDocument::where('user_id', $request->user()->id)->find($id);
        if (! $d) {
            return response()->json(['ok' => false, 'message' => 'Document introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'document' => $d->only(['id', 'fichier', 'fichier_nom', 'mime'])]);
    }

    // ── Équipe REJCC : pièces partagées volontairement ───────────────────

    /** GET /admin/documents-personnels/{id}/acces — seulement si le membre a partagé la pièce. */
    public function adminAcces(Request $request, int $id)
    {
        $d = PersonalDocument::where('partage', true)->find($id);
        if (! $d) {
            return response()->json(['ok' => false, 'message' => "Ce document n'est pas partagé avec l'équipe."], 404);
        }
        $d->update(['consulte_equipe_at' => now()]);
        // Toute consultation par l'équipe est tracée dans le journal d'audit.
        $admin = $request->user();
        \App\Models\AuditLog::create([
            'user_id' => $admin->id, 'actor' => trim($admin->prenom.' '.$admin->nom).' ('.$admin->email.')',
            'action' => 'Consultation', 'target' => "Document personnel #{$d->id} ({$d->libelle()}) du membre #{$d->user_id}",
            'method' => 'GET', 'path' => '/'.$request->path(), 'ip' => $request->ip(),
        ]);

        return response()->json(['ok' => true, 'document' => $d->only(['id', 'fichier', 'fichier_nom', 'mime'])]);
    }
}
