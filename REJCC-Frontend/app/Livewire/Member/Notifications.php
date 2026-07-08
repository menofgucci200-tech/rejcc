<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member')]
class Notifications extends Component
{
    public $items;

    public function mount(): void
    {
        $token = Api::token();

        $result = Api::get('/notifications', [], $token);

        $this->items = Collection::make($result['notifications'] ?? [])
            ->map(function ($n) {
                $n['created_at'] = Carbon::parse($n['created_at']);

                return (object) $n;
            });

        Api::post('/notifications/read-all', [], $token);
    }

    public function render()
    {
        return view('livewire.member.notifications');
    }
}
