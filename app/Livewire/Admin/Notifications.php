<?php

namespace App\Livewire\Admin;

use App\Models\MemberNotification;
use App\Models\User;
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

        $members = User::where('role', 'member')->pluck('id');

        foreach ($members as $uid) {
            MemberNotification::create([
                'user_id' => $uid,
                'type' => $this->type,
                'title' => $this->title,
                'body' => $this->body ?: null,
                'link' => $this->link ?: null,
            ]);
        }

        $this->sentTo = $members->count();
        $this->reset(['title', 'body', 'link', 'type']);
        $this->type = 'info';
    }

    public function render()
    {
        return view('livewire.admin.notifications');
    }
}
