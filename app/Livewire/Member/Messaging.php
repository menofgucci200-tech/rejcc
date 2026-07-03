<?php

namespace App\Livewire\Member;

use App\Models\MemberNotification;
use App\Models\Message;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member')]
class Messaging extends Component
{
    public ?int $activeId = null;

    public ?array $partner = null;

    public array $messages = [];

    public string $body = '';

    public function mount(): void
    {
        $to = request()->integer('to');

        if ($to) {
            $this->openThread($to);
        }
    }

    public function getConversationsProperty()
    {
        $me = auth()->id();

        $all = Message::where('sender_id', $me)->orWhere('recipient_id', $me)
            ->orderByDesc('created_at')
            ->get();

        $convos = [];
        foreach ($all as $m) {
            $other = $m->sender_id === $me ? $m->recipient_id : $m->sender_id;
            if (! isset($convos[$other])) {
                $convos[$other] = ['last' => $m->body, 'unread' => 0];
            }
            if ($m->recipient_id === $me && ! $m->read_at) {
                $convos[$other]['unread']++;
            }
        }

        $users = User::whereIn('id', array_keys($convos))->get(['id', 'prenom', 'nom'])->keyBy('id');

        $out = [];
        foreach ($convos as $uid => $c) {
            if (! isset($users[$uid])) {
                continue;
            }
            $out[] = [
                'user_id' => $uid,
                'prenom' => $users[$uid]->prenom,
                'nom' => $users[$uid]->nom,
                'last' => $c['last'],
                'unread' => $c['unread'],
            ];
        }

        return $out;
    }

    public function openThread(int $userId): void
    {
        $me = auth()->id();
        $this->activeId = $userId;

        Message::where('sender_id', $userId)->where('recipient_id', $me)
            ->whereNull('read_at')->update(['read_at' => now()]);

        $this->messages = Message::where(fn ($q) => $q->where('sender_id', $me)->where('recipient_id', $userId))
            ->orWhere(fn ($q) => $q->where('sender_id', $userId)->where('recipient_id', $me))
            ->orderBy('created_at')
            ->get(['id', 'sender_id', 'recipient_id', 'body', 'created_at'])
            ->toArray();

        $partner = User::find($userId, ['id', 'prenom', 'nom']);
        $this->partner = $partner ? ['id' => $partner->id, 'prenom' => $partner->prenom, 'nom' => $partner->nom] : null;
    }

    public function refreshThread(): void
    {
        if (! $this->activeId) {
            return;
        }

        $me = auth()->id();
        $userId = $this->activeId;

        Message::where('sender_id', $userId)->where('recipient_id', $me)
            ->whereNull('read_at')->update(['read_at' => now()]);

        $this->messages = Message::where(fn ($q) => $q->where('sender_id', $me)->where('recipient_id', $userId))
            ->orWhere(fn ($q) => $q->where('sender_id', $userId)->where('recipient_id', $me))
            ->orderBy('created_at')
            ->get(['id', 'sender_id', 'recipient_id', 'body', 'created_at'])
            ->toArray();
    }

    public function closeThread(): void
    {
        $this->activeId = null;
        $this->partner = null;
        $this->messages = [];
    }

    public function send(): void
    {
        $this->validate(['body' => 'required|string|min:1|max:2000']);

        if (! $this->activeId) {
            return;
        }

        $me = auth()->user();

        Message::create([
            'sender_id' => $me->id,
            'recipient_id' => $this->activeId,
            'body' => $this->body,
        ]);

        MemberNotification::create([
            'user_id' => $this->activeId,
            'type' => 'message',
            'title' => 'Nouveau message',
            'body' => $me->prenom.' '.$me->nom.' vous a écrit.',
            'link' => route('espace-membre.messaging', absolute: false),
        ]);

        $this->body = '';
        $this->openThread($this->activeId);
    }

    public function render()
    {
        return view('livewire.member.messaging');
    }
}
