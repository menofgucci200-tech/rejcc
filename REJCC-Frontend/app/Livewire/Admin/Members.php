<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Members extends Component
{
    public string $query = '';

    public function toggleRole(int $id): void
    {
        $members = Api::get('/admin/members', [], Api::token())['members'] ?? [];
        $member = Collection::make($members)->firstWhere('id', $id);

        if (! $member) {
            return;
        }

        $newRole = $member['role'] === 'admin' ? 'member' : 'admin';
        Api::put("/admin/members/{$id}", ['role' => $newRole], Api::token());
    }

    public function deleteMember(int $id): void
    {
        Api::delete("/admin/members/{$id}", Api::token());
    }

    public function render()
    {
        $q = trim($this->query);

        $members = Collection::make(Api::get('/admin/members', ['q' => $q], Api::token())['members'] ?? [])
            ->map(function ($m) {
                $m['created_at'] = Carbon::parse($m['created_at']);

                return (object) $m;
            });

        return view('livewire.admin.members', ['members' => $members]);
    }
}
