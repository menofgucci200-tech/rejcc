<?php

namespace App\Livewire\Member;

use App\Models\MemberNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member')]
class Notifications extends Component
{
    public $items;

    public function mount(): void
    {
        $this->items = MemberNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        MemberNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('livewire.member.notifications');
    }
}
