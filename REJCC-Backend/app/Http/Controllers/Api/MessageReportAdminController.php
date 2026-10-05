<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use App\Models\Message;
use App\Models\MessageReport;
use Illuminate\Http\Request;

/**
 * Signalements de conversations (section « messagerie »). L'administration
 * ne voit une conversation privée que si l'un de ses participants l'a
 * signalée.
 */
class MessageReportAdminController extends Controller
{
    private function nom($u): string
    {
        return $u ? trim($u->prenom.' '.$u->nom) : 'Compte supprimé';
    }

    /** GET /admin/signalements-messages?statut=nouveau|traite */
    public function index(Request $request)
    {
        $statut = $request->query('statut', 'nouveau') === 'traite' ? 'traite' : 'nouveau';
        $page = MessageReport::with(['reporter:id,prenom,nom', 'reported:id,prenom,nom,is_active'])
            ->where('statut', $statut)->latest()->paginate(30);

        return response()->json([
            'ok' => true,
            'signalements' => collect($page->items())->map(fn (MessageReport $r) => [
                'id' => $r->id,
                'auteur' => $this->nom($r->reporter),
                'auteur_id' => $r->reporter_id,
                'signale' => $this->nom($r->reported),
                'signale_id' => $r->reported_id,
                'signale_actif' => (bool) ($r->reported->is_active ?? false),
                'motif' => $r->motif,
                'decision' => $r->decision,
                'date' => $r->created_at?->toIso8601String(),
                'messages' => Message::where(fn ($q) => $q->where('sender_id', $r->reporter_id)->where('recipient_id', $r->reported_id))
                    ->orWhere(fn ($q) => $q->where('sender_id', $r->reported_id)->where('recipient_id', $r->reporter_id))->count(),
            ])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
        ]);
    }

    /** GET /admin/signalements-messages/{id} — la conversation signalée (100 derniers messages). */
    public function show(int $id)
    {
        $r = MessageReport::with(['reporter:id,prenom,nom', 'reported:id,prenom,nom'])->find($id);
        if (! $r) {
            return response()->json(['ok' => false, 'message' => 'Signalement introuvable.'], 404);
        }

        $messages = Message::where(fn ($q) => $q->where('sender_id', $r->reporter_id)->where('recipient_id', $r->reported_id))
            ->orWhere(fn ($q) => $q->where('sender_id', $r->reported_id)->where('recipient_id', $r->reporter_id))
            ->latest('id')->limit(100)->get()->reverse()->values()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'de_signale' => $m->sender_id === $r->reported_id,
                'body' => $m->body,
                'date' => $m->created_at?->toIso8601String(),
            ]);

        return response()->json(['ok' => true, 'signalement' => [
            'id' => $r->id,
            'auteur' => $this->nom($r->reporter),
            'signale' => $this->nom($r->reported),
            'signale_id' => $r->reported_id,
            'motif' => $r->motif,
            'statut' => $r->statut,
        ], 'messages' => $messages]);
    }

    /**
     * PUT /admin/signalements-messages/{id} — decision=classe (sans suite)
     * ou averti (le membre signalé reçoit un avertissement). La suspension
     * du compte se fait depuis « Comptes membres ».
     */
    public function traiter(Request $request, int $id)
    {
        $r = MessageReport::find($id);
        if (! $r) {
            return response()->json(['ok' => false, 'message' => 'Signalement introuvable.'], 404);
        }
        $decision = $request->input('decision');
        if (! in_array($decision, ['classe', 'averti'], true)) {
            return response()->json(['ok' => false, 'message' => 'Décision inconnue.'], 422);
        }

        $r->update(['statut' => 'traite', 'decision' => $decision, 'traite_par' => $request->user()->id, 'traite_at' => now()]);

        if ($decision === 'averti') {
            MemberNotification::create([
                'user_id' => $r->reported_id,
                'type' => 'warning',
                'title' => 'Avertissement de la modération',
                'body' => "Un de vos échanges dans la messagerie a été signalé et ne respecte pas la charte du membre. Merci de garder des échanges courtois et bienveillants ; en cas de récidive, votre compte pourra être suspendu.",
                'link' => '/charte-du-membre',
            ]);
        }

        MemberNotification::create([
            'user_id' => $r->reporter_id,
            'type' => 'info',
            'title' => 'Votre signalement a été traité',
            'body' => $decision === 'averti'
                ? "Merci pour votre signalement : le membre concerné a reçu un avertissement de la modération."
                : "Merci pour votre signalement : après examen, l'administration n'a pas relevé de manquement à la charte.",
            'link' => '/espace-membre/messagerie',
        ]);

        return response()->json(['ok' => true]);
    }
}
