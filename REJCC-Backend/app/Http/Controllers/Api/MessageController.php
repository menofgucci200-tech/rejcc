<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Messagerie privée entre membres : une conversation par interlocuteur.
 */
class MessageController extends Controller
{
    /** Informations affichées sur l'interlocuteur (liste et en-tête du fil). */
    private function interlocuteur(User $u): array
    {
        return [
            'id' => $u->id,
            'prenom' => $u->prenom,
            'nom' => $u->nom,
            'photo' => $u->photo,
            'role' => $u->role,
            'role_label' => $u->roleLabel(),
            'titre' => $u->titre,
            'ville' => $u->ville,
            'actif' => (bool) $u->is_active,
        ];
    }

    /** GET /messages?q= — conversations, la plus récente d'abord. */
    public function conversations(Request $request)
    {
        $me = $request->user()->id;

        $lignes = Message::query()
            ->selectRaw('CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END as autre, MAX(id) as dernier', [$me])
            ->where(fn ($q) => $q->where('sender_id', $me)->orWhere('recipient_id', $me))
            ->groupBy('autre')
            ->orderByDesc('dernier')
            ->get();

        $derniers = Message::whereIn('id', $lignes->pluck('dernier'))->get()->keyBy('id');
        $nonLus = Message::where('recipient_id', $me)->whereNull('read_at')
            ->selectRaw('sender_id, count(*) as n')->groupBy('sender_id')->pluck('n', 'sender_id');
        $users = User::whereIn('id', $lignes->pluck('autre'))->get()->keyBy('id');

        $q = mb_strtolower(trim((string) $request->query('q', '')));

        $out = [];
        foreach ($lignes as $l) {
            $u = $users[$l->autre] ?? null;
            $m = $derniers[$l->dernier] ?? null;
            if (! $u || ! $m) {
                continue;
            }
            if ($q !== '' && ! str_contains(mb_strtolower($u->prenom.' '.$u->nom), $q)) {
                continue;
            }
            $out[] = $this->interlocuteur($u) + [
                'user_id' => $u->id,
                'last' => $m->body,
                'last_moi' => $m->sender_id === $me,
                'last_vu' => $m->sender_id === $me && $m->read_at !== null,
                'at' => $m->created_at?->toIso8601String(),
                'unread' => (int) ($nonLus[$u->id] ?? 0),
            ];
        }

        return response()->json(['ok' => true, 'conversations' => $out]);
    }

    /**
     * GET /messages/{userId}?after= — fil de discussion (ou seulement les
     * messages postérieurs à `after`), et marque les messages reçus comme lus.
     */
    public function thread(Request $request, int $userId)
    {
        $me = $request->user()->id;
        $partner = User::find($userId);
        if (! $partner || $partner->id === $me) {
            return response()->json(['ok' => false, 'message' => 'Ce membre est introuvable.'], 404);
        }

        Message::where('sender_id', $userId)->where('recipient_id', $me)
            ->whereNull('read_at')->update(['read_at' => now()]);

        $query = Message::where(fn ($q) => $q->where(fn ($w) => $w->where('sender_id', $me)->where('recipient_id', $userId))
            ->orWhere(fn ($w) => $w->where('sender_id', $userId)->where('recipient_id', $me)));

        $after = (int) $request->query('after', 0);
        $messages = (clone $query)->when($after > 0, fn ($q) => $q->where('id', '>', $after))
            ->orderBy('id')->get(['id', 'sender_id', 'recipient_id', 'body', 'created_at', 'read_at']);

        return response()->json([
            'ok' => true,
            'me' => $me,
            'partner' => $this->interlocuteur($partner),
            'messages' => $messages,
            // Dernier de mes messages lu par l'interlocuteur (« Vu »).
            'vu_jusqua' => (int) (clone $query)->where('sender_id', $me)->whereNotNull('read_at')->max('id'),
            'peut_ecrire' => $partner->is_active,
        ]);
    }

    /** POST /messages — envoyer un message. */
    public function send(Request $request)
    {
        $me = $request->user();

        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|integer',
            'body' => 'required|string|max:2000',
        ], [
            'body.required' => 'Écrivez votre message avant de l\'envoyer.',
            'body.max' => 'Votre message est trop long (2000 caractères maximum).',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $body = trim((string) $request->input('body'));
        if ($body === '') {
            return response()->json(['ok' => false, 'message' => 'Écrivez votre message avant de l\'envoyer.'], 422);
        }

        $destinataire = User::find((int) $request->input('recipient_id'));
        if (! $destinataire || $destinataire->id === $me->id) {
            return response()->json(['ok' => false, 'message' => 'Ce destinataire est introuvable.'], 422);
        }
        if (! $destinataire->is_active) {
            return response()->json(['ok' => false, 'message' => "Ce compte est suspendu : il ne peut pas recevoir de messages."], 422);
        }

        $message = Message::create([
            'sender_id' => $me->id,
            'recipient_id' => $destinataire->id,
            'body' => $body,
        ]);

        MemberNotification::create([
            'user_id' => $destinataire->id,
            'type' => 'message',
            'title' => 'Nouveau message',
            'body' => $me->prenom.' '.$me->nom.' vous a écrit.',
            'link' => '/espace-membre/messagerie',
        ]);

        return response()->json(['ok' => true, 'message' => $message->only(['id', 'sender_id', 'recipient_id', 'body', 'created_at', 'read_at'])]);
    }
}
