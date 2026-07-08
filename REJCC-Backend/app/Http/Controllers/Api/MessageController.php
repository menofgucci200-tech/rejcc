<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /** Liste des conversations (un interlocuteur = une conversation). */
    public function conversations(Request $request)
    {
        $me = $request->user()->id;

        $messages = Message::where('sender_id', $me)
            ->orWhere('recipient_id', $me)
            ->orderByDesc('created_at')
            ->get();

        $convos = [];
        foreach ($messages as $m) {
            $other = $m->sender_id === $me ? $m->recipient_id : $m->sender_id;
            if (! isset($convos[$other])) {
                $convos[$other] = ['last' => $m->body, 'at' => $m->created_at, 'unread' => 0];
            }
            if ($m->recipient_id === $me && ! $m->read_at) {
                $convos[$other]['unread']++;
            }
        }

        $users = User::whereIn('id', array_keys($convos))->get(['id', 'prenom', 'nom'])->keyBy('id');

        $out = [];
        foreach ($convos as $uid => $c) {
            $u = $users[$uid] ?? null;
            if (! $u) {
                continue;
            }
            $out[] = [
                'user_id' => $uid,
                'prenom' => $u->prenom,
                'nom' => $u->nom,
                'last' => $c['last'],
                'at' => $c['at'],
                'unread' => $c['unread'],
            ];
        }

        return response()->json(['ok' => true, 'conversations' => $out]);
    }

    /** Fil de discussion avec un membre (et marque les reçus comme lus). */
    public function thread(Request $request, int $userId)
    {
        $me = $request->user()->id;

        Message::where('sender_id', $userId)->where('recipient_id', $me)
            ->whereNull('read_at')->update(['read_at' => now()]);

        $messages = Message::where(fn ($q) => $q->where('sender_id', $me)->where('recipient_id', $userId))
            ->orWhere(fn ($q) => $q->where('sender_id', $userId)->where('recipient_id', $me))
            ->orderBy('created_at')
            ->get(['id', 'sender_id', 'recipient_id', 'body', 'created_at']);

        $partner = User::find($userId, ['id', 'prenom', 'nom']);

        return response()->json(['ok' => true, 'me' => $me, 'partner' => $partner, 'messages' => $messages]);
    }

    public function send(Request $request)
    {
        $me = $request->user();

        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|integer|exists:users,id',
            'body' => 'required|string|min:1|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $d = $validator->validated();
        if ((int) $d['recipient_id'] === $me->id) {
            return response()->json(['ok' => false, 'message' => 'Destinataire invalide.'], 422);
        }

        $message = Message::create([
            'sender_id' => $me->id,
            'recipient_id' => $d['recipient_id'],
            'body' => $d['body'],
        ]);

        MemberNotification::create([
            'user_id' => $d['recipient_id'],
            'type' => 'message',
            'title' => 'Nouveau message',
            'body' => $me->prenom . ' ' . $me->nom . ' vous a écrit.',
            'link' => '/espace-membre/messagerie',
        ]);

        return response()->json(['ok' => true, 'message' => $message]);
    }
}
