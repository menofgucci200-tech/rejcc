<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
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
        $historique = Collection::make(Api::get('/admin/notifications/history', [], Api::token())['historique'] ?? [])
            ->map(function ($h) {
                $h['created_at'] = \Carbon\Carbon::parse($h['created_at']);

                return (object) $h;
            });

        return view('livewire.admin.notifications', ['historique' => $historique]);
    }
}
