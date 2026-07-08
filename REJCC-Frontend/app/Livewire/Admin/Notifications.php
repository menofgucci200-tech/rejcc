<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Notifications extends Component
{
    public string $type = 'info';

    public string $title = '';

    public string $body = '';

    public string $link = '';

    public ?int $sentTo = null;

    public function send(): void
    {
        $this->validate([
            'title' => 'required|string|max:150',
            'body' => 'nullable|string|max:500',
            'link' => 'nullable|string|max:300',
            'type' => 'in:info,message,alert',
        ]);

        $result = Api::post('/admin/notifications/broadcast', [
            'title' => $this->title,
            'body' => $this->body ?: null,
            'link' => $this->link ?: null,
            'type' => $this->type,
        ], Api::token());

        $this->sentTo = $result['sent_to'] ?? 0;
        $this->reset(['title', 'body', 'link', 'type']);
        $this->type = 'info';
    }

    public function render()
    {
        return view('livewire.admin.notifications');
    }
}
