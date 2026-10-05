<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use App\Models\MentoringSession;
use App\Models\Mentorship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Séances de mentorat : l'un des deux participants propose un créneau,
 * l'autre le confirme ; après la séance, le mentor en rédige le compte
 * rendu et les prochaines étapes, visibles des deux.
 */
class SeanceController extends Controller
{
    private function nomDe(User $u): string
    {
        return trim("{$u->prenom} {$u->nom}");
    }

    private function notifier(int $userId, string $titre, string $texte, int $mentorshipId): void
    {
        MemberNotification::create([
            'user_id' => $userId,
            'type' => 'info',
            'title' => $titre,
            'body' => $texte,
            'link' => "/espace-membre/mentorat/{$mentorshipId}",
        ]);
    }

    private function seance(MentoringSession $s, User $me): array
    {
        return [
            'id' => $s->id,
            'debut' => $s->debut_at->toIso8601String(),
            'fin' => $s->debut_at->copy()->addMinutes($s->duree_minutes)->toIso8601String(),
            'duree_minutes' => $s->duree_minutes,
            'format' => $s->format,
            'lieu' => $s->lieu,
            'ordre_du_jour' => $s->ordre_du_jour,
            'statut' => $s->statut,
            'statut_label' => MentoringSession::STATUTS[$s->statut] ?? $s->statut,
            'motif_annulation' => $s->motif_annulation,
            'compte_rendu' => $s->compte_rendu,
            'prochaines_etapes' => $s->prochaines_etapes,
            'proposee_par_moi' => $s->propose_par === $me->id,
            'passee' => $s->debut_at->isPast(),
        ];
    }

    /** Mentorat dont l'utilisateur courant est participant. */
    private function mentorat(Request $request, int $id): ?Mentorship
    {
        $m = Mentorship::with(['mentor', 'mentore', 'seances'])->find($id);

        return $m && $m->participe($request->user()->id) ? $m : null;
    }

    /** GET /mentorat/{id} — suivi d'un mentorat (participants et séances). */
    public function show(Request $request, int $id)
    {
        $m = $this->mentorat($request, $id);
        if (! $m) {
            return response()->json(['ok' => false, 'message' => 'Mentorat introuvable.'], 404);
        }
        $me = $request->user();
        $autre = $m->mentor_id === $me->id ? $m->mentore : $m->mentor;

        return response()->json(['ok' => true, 'mentorat' => [
            'id' => $m->id,
            'statut' => $m->statut,
            'statut_label' => Mentorship::STATUTS[$m->statut] ?? $m->statut,
            'objectif' => $m->objectif,
            'besoin' => $m->besoin,
            'debut' => $m->repondu_at?->toIso8601String(),
            'termine_le' => $m->termine_at?->toIso8601String(),
            'je_suis' => $m->mentor_id === $me->id ? 'mentor' : 'mentore',
            'autre' => [
                'id' => $autre->id, 'prenom' => $autre->prenom, 'nom' => $autre->nom, 'photo' => $autre->photo,
                'titre' => $autre->titre, 'secteur' => $autre->secteur, 'ville' => $autre->ville,
            ],
            'format_mentor' => $m->mentor->mentor_format,
            'bilan' => $m->bilan,
            'termine_par_moi' => $m->termine_par === $me->id,
            'note' => $m->note,
            'avis' => $m->avis,
            'peut_evaluer' => $m->statut === 'termine' && $m->mentore_id === $me->id && $m->evalue_at === null,
            'seances' => $m->seances->map(fn ($s) => $this->seance($s, $me))->values(),
        ]]);
    }

    /** POST /mentorat/{id}/seances — proposer un créneau. */
    public function proposer(Request $request, int $id)
    {
        $m = $this->mentorat($request, $id);
        if (! $m) {
            return response()->json(['ok' => false, 'message' => 'Mentorat introuvable.'], 404);
        }
        if ($m->statut !== 'accepte') {
            return response()->json(['ok' => false, 'message' => 'Les séances se planifient pendant un mentorat en cours.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'debut' => 'required|date|after:now|before:+1 year',
            'duree_minutes' => 'required|integer|in:30,45,60,90,120',
            'format' => 'required|in:visio,presentiel',
            'lieu' => 'nullable|string|max:255',
            'ordre_du_jour' => 'nullable|string|max:1000',
        ], [
            'debut.required' => 'Choisissez la date et l\'heure de la séance.',
            'debut.after' => 'Choisissez une date à venir.',
            'debut.before' => 'Choisissez une date dans l\'année qui vient.',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $me = $request->user();
        $s = $m->seances()->create([
            'propose_par' => $me->id,
            'statut' => 'proposee',
            'debut_at' => Carbon::parse($request->input('debut')),
            'duree_minutes' => (int) $request->input('duree_minutes'),
            'format' => $request->input('format'),
            'lieu' => $request->input('lieu'),
            'ordre_du_jour' => $request->input('ordre_du_jour'),
        ]);

        $autreId = $m->mentor_id === $me->id ? $m->mentore_id : $m->mentor_id;
        $this->notifier($autreId, 'Séance de mentorat proposée', $this->nomDe($me).' propose une séance le '.$s->debut_at->translatedFormat('l j F à H\hi').' : confirmez-la depuis votre suivi de mentorat.', $m->id);

        return response()->json(['ok' => true, 'seance' => $this->seance($s, $me)]);
    }

    private function seanceDe(Request $request, int $id): ?MentoringSession
    {
        $s = MentoringSession::with('mentorship')->find($id);

        return $s && $s->mentorship->participe($request->user()->id) ? $s : null;
    }

    /** POST /seances/{id}/confirmer — l'autre participant confirme le créneau. */
    public function confirmer(Request $request, int $id)
    {
        $s = $this->seanceDe($request, $id);
        $me = $request->user();
        if (! $s || $s->statut !== 'proposee') {
            return response()->json(['ok' => false, 'message' => 'Séance introuvable ou déjà traitée.'], 404);
        }
        if ($s->propose_par === $me->id) {
            return response()->json(['ok' => false, 'message' => 'C\'est à l\'autre participant de confirmer ce créneau.'], 422);
        }
        if ($s->debut_at->isPast()) {
            return response()->json(['ok' => false, 'message' => 'Ce créneau est passé : proposez-en un nouveau.'], 422);
        }

        $s->update(['statut' => 'confirmee']);
        $this->notifier($s->propose_par, 'Séance de mentorat confirmée', $this->nomDe($me).' a confirmé la séance du '.$s->debut_at->translatedFormat('l j F à H\hi').'.', $s->mentorship_id);

        return response()->json(['ok' => true, 'seance' => $this->seance($s, $me)]);
    }

    /** POST /seances/{id}/annuler — l'un ou l'autre annule (motif facultatif). */
    public function annuler(Request $request, int $id)
    {
        $s = $this->seanceDe($request, $id);
        $me = $request->user();
        if (! $s || ! in_array($s->statut, ['proposee', 'confirmee'], true)) {
            return response()->json(['ok' => false, 'message' => 'Séance introuvable ou déjà traitée.'], 404);
        }

        $s->update(['statut' => 'annulee', 'motif_annulation' => mb_substr(trim((string) $request->input('motif', '')), 0, 300) ?: null]);
        $autreId = $s->mentorship->mentor_id === $me->id ? $s->mentorship->mentore_id : $s->mentorship->mentor_id;
        $this->notifier($autreId, 'Séance de mentorat annulée', $this->nomDe($me).' a annulé la séance du '.$s->debut_at->translatedFormat('l j F à H\hi').($s->motif_annulation ? " : « {$s->motif_annulation} »" : '.'), $s->mentorship_id);

        return response()->json(['ok' => true]);
    }

    /** POST /seances/{id}/compte-rendu — le mentor clôt une séance passée par un compte rendu. */
    public function compteRendu(Request $request, int $id)
    {
        $s = $this->seanceDe($request, $id);
        $me = $request->user();
        if (! $s || ! in_array($s->statut, ['confirmee', 'realisee'], true)) {
            return response()->json(['ok' => false, 'message' => 'Séance introuvable.'], 404);
        }
        if ($s->mentorship->mentor_id !== $me->id) {
            return response()->json(['ok' => false, 'message' => 'Le compte rendu est rédigé par le mentor.'], 403);
        }
        if ($s->debut_at->isFuture()) {
            return response()->json(['ok' => false, 'message' => 'Le compte rendu se rédige une fois la séance commencée.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'compte_rendu' => 'required|string|min:10|max:3000',
            'prochaines_etapes' => 'nullable|string|max:1500',
        ], ['compte_rendu.required' => 'Résumez la séance en quelques lignes.', 'compte_rendu.min' => 'Résumez la séance en quelques lignes.']);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $nouveau = $s->statut !== 'realisee';
        $s->update(['statut' => 'realisee', 'compte_rendu' => trim($request->input('compte_rendu')), 'prochaines_etapes' => trim((string) $request->input('prochaines_etapes')) ?: null]);
        if ($nouveau) {
            $this->notifier($s->mentorship->mentore_id, 'Compte rendu de votre séance', $this->nomDe($me).' a partagé le compte rendu et les prochaines étapes de votre séance.', $s->mentorship_id);
        }

        return response()->json(['ok' => true, 'seance' => $this->seance($s, $me)]);
    }

    /** GET /mentorat/prochaine-seance — pour le tableau de bord. */
    public function prochaine(Request $request)
    {
        $me = $request->user();
        $s = MentoringSession::with(['mentorship.mentor', 'mentorship.mentore'])
            ->whereIn('statut', ['proposee', 'confirmee'])
            ->where('debut_at', '>', now()->subHour())
            ->whereHas('mentorship', fn ($q) => $q->where('statut', 'accepte')->where(fn ($w) => $w->where('mentor_id', $me->id)->orWhere('mentore_id', $me->id)))
            ->orderBy('debut_at')
            ->first();

        if (! $s) {
            return response()->json(['ok' => true, 'seance' => null]);
        }
        $autre = $s->mentorship->mentor_id === $me->id ? $s->mentorship->mentore : $s->mentorship->mentor;

        return response()->json(['ok' => true, 'seance' => $this->seance($s, $me) + [
            'mentorship_id' => $s->mentorship_id,
            'avec' => $this->nomDe($autre),
            'je_suis' => $s->mentorship->mentor_id === $me->id ? 'mentor' : 'mentore',
        ]]);
    }

    /** POST /mentorat/{id}/terminer — l'un ou l'autre clôt le mentorat (bilan facultatif). */
    public function terminer(Request $request, int $id)
    {
        $m = $this->mentorat($request, $id);
        if (! $m || $m->statut !== 'accepte') {
            return response()->json(['ok' => false, 'message' => 'Mentorat introuvable ou déjà terminé.'], 404);
        }
        $bilan = trim((string) $request->input('bilan', '')) ?: null;
        if ($bilan !== null && mb_strlen($bilan) > 2000) {
            return response()->json(['ok' => false, 'message' => 'Le bilan est trop long (2000 caractères au plus).'], 422);
        }

        $me = $request->user();
        $m->update(['statut' => 'termine', 'termine_at' => now(), 'termine_par' => $me->id, 'bilan' => $bilan]);
        // Les séances encore prévues sont annulées.
        $m->seances()->whereIn('statut', ['proposee', 'confirmee'])->where('debut_at', '>', now())
            ->update(['statut' => 'annulee', 'motif_annulation' => 'Mentorat terminé']);

        $estMentor = $m->mentor_id === $me->id;
        $this->notifier(
            $estMentor ? $m->mentore_id : $m->mentor_id,
            'Mentorat terminé',
            $this->nomDe($me).' a clôturé le mentorat « '.$m->objectif.' ».'.($estMentor ? ' Donnez votre avis sur cet accompagnement.' : ''),
            $m->id,
        );

        return response()->json(['ok' => true]);
    }

    /** POST /mentorat/{id}/evaluer — le mentoré évalue son mentor (une fois). */
    public function evaluer(Request $request, int $id)
    {
        $m = $this->mentorat($request, $id);
        $me = $request->user();
        if (! $m || $m->mentore_id !== $me->id || $m->statut !== 'termine') {
            return response()->json(['ok' => false, 'message' => 'Seul le mentoré peut évaluer un mentorat terminé.'], 403);
        }
        if ($m->evalue_at) {
            return response()->json(['ok' => false, 'message' => 'Vous avez déjà donné votre avis sur ce mentorat.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'note' => 'required|integer|min:1|max:5',
            'avis' => 'nullable|string|max:1500',
        ], ['note.required' => 'Choisissez une note de 1 à 5 étoiles.']);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $m->update(['note' => (int) $request->input('note'), 'avis' => trim((string) $request->input('avis')) ?: null, 'evalue_at' => now()]);
        $this->notifier($m->mentor_id, 'Nouvel avis sur votre mentorat', $this->nomDe($me).' a évalué votre accompagnement : '.$m->note.'/5.', $m->id);

        return response()->json(['ok' => true]);
    }
}
