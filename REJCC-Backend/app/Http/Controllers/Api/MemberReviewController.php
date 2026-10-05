<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use App\Models\MemberReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Avis des membres sur les professionnels du réseau (groupes sectoriels) :
 * une note de 1 à 5 et un commentaire, un avis par membre et par
 * professionnel, modifiable ou supprimable par son auteur.
 */
class MemberReviewController extends Controller
{
    /** Liste publique (pour les membres abonnés) des avis visibles d'un membre. */
    public static function avisDe(int $userId, int $moiId): array
    {
        $liste = MemberReview::with(['reviewer:id,prenom,nom,photo', 'group:id,name'])
            ->where('reviewed_id', $userId)->where('masque', false)
            ->latest('updated_at')->limit(30)->get();

        // Répartition calculée sur tous les avis visibles (la liste est limitée à 30).
        $repartition = MemberReview::where('reviewed_id', $userId)->where('masque', false)
            ->selectRaw('note, count(*) as n')->groupBy('note')->pluck('n', 'note');

        $mien = MemberReview::where('reviewed_id', $userId)->where('reviewer_id', $moiId)->first();

        return MemberReview::resume($userId) + [
            'repartition' => collect(range(5, 1))->mapWithKeys(fn ($n) => [$n => (int) ($repartition[$n] ?? 0)])->all(),
            'liste' => $liste->map(fn (MemberReview $r) => [
                'id' => $r->id,
                'auteur' => trim(($r->reviewer->prenom ?? '').' '.($r->reviewer->nom ?? '')),
                'auteur_photo' => $r->reviewer->photo ?? null,
                'auteur_id' => $r->reviewer_id,
                'note' => $r->note,
                'commentaire' => $r->commentaire,
                'groupe' => $r->group?->name,
                'signale' => $r->signale_at !== null,
                'date' => $r->updated_at?->toIso8601String(),
            ])->values()->all(),
            // Son propre avis, même masqué par la modération (avec l'indication).
            'mon_avis' => $mien ? ['note' => $mien->note, 'commentaire' => $mien->commentaire, 'masque' => $mien->masque] : null,
        ];
    }

    /** POST /members/{id}/avis — donner ou modifier son avis. */
    public function store(Request $request, int $id)
    {
        $moi = $request->user();
        $pro = User::whereIn('role', ['member', 'mentor'])->where('is_active', true)->find($id);
        if (! $pro) {
            return response()->json(['ok' => false, 'message' => 'Membre introuvable.'], 404);
        }
        if ($pro->id === $moi->id) {
            return response()->json(['ok' => false, 'message' => 'Vous ne pouvez pas donner un avis sur vous-même.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'note' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:1000',
            'group_id' => 'nullable|integer|exists:groups,id',
        ], ['note.required' => 'Choisissez une note de 1 à 5 étoiles.']);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $avis = MemberReview::firstOrNew(['reviewer_id' => $moi->id, 'reviewed_id' => $pro->id]);
        $avis->fill([
            'note' => (int) $request->input('note'),
            'commentaire' => trim((string) $request->input('commentaire')) ?: null,
            'group_id' => $request->input('group_id'),
        ]);
        // Un avis masqué puis corrigé par son auteur redevient visible et
        // repasse devant la modération.
        if ($avis->exists && $avis->masque) {
            $avis->fill(['masque' => false, 'signale_at' => now(), 'signale_par' => null, 'motif_signalement' => 'Avis corrigé par son auteur après masquage']);
        }
        $avis->save();

        if ($avis->wasRecentlyCreated) {
            MemberNotification::create([
                'user_id' => $pro->id,
                'type' => 'info',
                'title' => 'Nouvel avis sur votre fiche',
                'body' => trim("{$moi->prenom} {$moi->nom}")." vous a attribué {$avis->note}/5".($avis->commentaire ? " : « {$avis->commentaire} »" : '.'),
                'link' => $avis->group_id ? "/espace-membre/groupes/{$avis->group_id}" : '/espace-membre/annuaire',
            ]);
        }

        return response()->json(['ok' => true, 'avis' => self::avisDe($pro->id, $moi->id)]);
    }

    /** DELETE /members/{id}/avis — retirer son avis. */
    public function destroy(Request $request, int $id)
    {
        MemberReview::where('reviewer_id', $request->user()->id)->where('reviewed_id', $id)->delete();

        return response()->json(['ok' => true, 'avis' => self::avisDe($id, $request->user()->id)]);
    }

    /**
     * POST /avis/{id}/signaler — signaler un avis abusif à l'administration.
     * L'avis reste visible jusqu'à la décision de la modération.
     */
    public function signaler(Request $request, int $id)
    {
        $avis = MemberReview::where('masque', false)->find($id);
        if (! $avis) {
            return response()->json(['ok' => false, 'message' => 'Avis introuvable.'], 404);
        }
        if ($avis->reviewer_id === $request->user()->id) {
            return response()->json(['ok' => false, 'message' => 'Vous ne pouvez pas signaler votre propre avis.'], 422);
        }

        $avis->update([
            'signale_at' => now(),
            'signale_par' => $request->user()->id,
            'motif_signalement' => mb_substr(trim((string) $request->input('motif')), 0, 300) ?: null,
        ]);

        return response()->json(['ok' => true, 'message' => "Merci, l'avis a été signalé à l'administration."]);
    }
}
