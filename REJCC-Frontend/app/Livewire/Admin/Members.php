<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Members extends Component
{
    public string $filtre = 'tous';

    public string $recherche = '';

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    public function toggleStatut(int $id): void
    {
        $member = Collection::make(Api::get('/admin/members', [], Api::token())['members'] ?? [])
            ->firstWhere('id', $id);

        if (! $member) {
            return;
        }

        Api::put("/admin/members/{$id}", ['is_active' => ! ($member['is_active'] ?? true)], Api::token());
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/members/{$id}", Api::token());
    }

    public function render()
    {
        $tous = Collection::make(Api::get('/admin/members', [], Api::token(), )['members'] ?? [])
            ->when($this->recherche !== '', function ($c) {
                $q = mb_strtolower($this->recherche);

                return $c->filter(fn ($m) => str_contains(mb_strtolower(($m['prenom'] ?? '').' '.($m['nom'] ?? '').' '.($m['email'] ?? '')), $q));
            })
            ->when($this->filtre === 'actifs', fn ($c) => $c->filter(fn ($m) => $m['is_active'] ?? true))
            ->when($this->filtre === 'suspendus', fn ($c) => $c->filter(fn ($m) => ! ($m['is_active'] ?? true)))
            ->map(fn ($m) => [
                'id' => $m['id'],
                'nom' => trim(($m['prenom'] ?? '').' '.($m['nom'] ?? '')) ?: $m['email'],
                'email' => $m['email'],
                'telephone' => $m['telephone'] ?? '—',
                'ville' => $m['ville'] ?? null,
                'role' => $m['role'],
                'restreint' => $m['role'] === 'admin' && ($m['permissions'] ?? null) !== null,
                'actif' => (bool) ($m['is_active'] ?? true),
                'depuis' => Carbon::parse($m['created_at'])->translatedFormat('M Y'),
                'initiales' => mb_strtoupper(mb_substr($m['prenom'] ?? $m['email'], 0, 1).mb_substr($m['nom'] ?? '', 0, 1)),
            ]);

        // Classement par rôle : administrateurs, puis mentors, puis membres.
        $groupes = collect([
            'Administrateurs' => $tous->where('role', 'admin')->values(),
            'Mentors' => $tous->where('role', 'mentor')->values(),
            'Membres' => $tous->where('role', 'member')->values(),
        ])->filter(fn ($g) => $g->isNotEmpty());

        return view('livewire.admin.members', [
            'groupes' => $groupes,
            'total' => $tous->count(),
        ]);
    }
}
