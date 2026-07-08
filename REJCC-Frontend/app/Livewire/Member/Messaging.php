<?php

namespace App\Livewire\Member;

use App\Support\Api;
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
        $result = Api::get('/messages', [], Api::token());

        return $result['conversations'] ?? [];
    }

    public function openThread(int $userId): void
    {
        $this->activeId = $userId;

        $result = Api::get("/messages/{$userId}", [], Api::token());

        $this->partner = $result['partner'] ?? null;
        $this->messages = $result['messages'] ?? [];
    }

    public function refreshThread(): void
    {
        if (! $this->activeId) {
            return;
        }

        $result = Api::get("/messages/{$this->activeId}", [], Api::token());

        $this->messages = $result['messages'] ?? [];
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

        Api::post('/messages', [
            'recipient_id' => $this->activeId,
            'body' => $this->body,
        ], Api::token());

        $this->body = '';
        $this->openThread($this->activeId);
    }

    public function render()
    {
        return view('livewire.member.messaging');
    }
}
