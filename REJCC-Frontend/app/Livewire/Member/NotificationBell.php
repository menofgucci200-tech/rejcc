<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Cloche de notifications de la barre du haut : panneau déroulant avec les
 * dernières notifications, badge des non-lues rafraîchi automatiquement
 * (wire:poll), sans dépendre d'une navigation.
 */
class NotificationBell extends Component
{
    public function markAllRead(): void
    {
        Api::post('/notifications/read-all', [], Api::token());
    }

    public function render()
    {
        $result = Api::get('/notifications', [], Api::token());

        $items = collect($result['notifications'] ?? [])
            ->take(8)
            ->map(function (array $n) {
                $n['created_at'] = Carbon::parse($n['created_at']);

                return (object) $n;
            });

        return view('livewire.member.notification-bell', [
            'unread' => (int) ($result['unread'] ?? 0),
            'items' => $items,
        ]);
    }
}
