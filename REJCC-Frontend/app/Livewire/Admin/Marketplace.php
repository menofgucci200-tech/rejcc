<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Modération de la Marketplace : valider ou refuser les annonces soumises
 * par les membres, retirer une annonce publiée.
 */
#[Layout('layouts.admin-light')]
class Marketplace extends Component
{
    public string $filtre = 'en_attente'; // en_attente | approuve | refuse | tous

    public ?int $rejectingId = null;

    public string $motif = '';

    public ?int $expandedId = null;

    public function setFiltre(string $filtre): void
    {
        if (in_array($filtre, ['en_attente', 'approuve', 'refuse', 'tous'], true)) {
            $this->filtre = $filtre;
            $this->rejectingId = null;
        }
    }

    public function toggleDetail(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function approve(int $id): void
    {
        Api::put("/admin/marketplace/{$id}/approve", [], Api::token());
        $this->rejectingId = null;
    }

    public function openReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->motif = '';
        $this->resetValidation();
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
    }

    public function confirmReject(): void
    {
        $this->validate(['motif' => 'nullable|string|max:300']);

        if ($this->rejectingId) {
            Api::put("/admin/marketplace/{$this->rejectingId}/reject", ['motif' => $this->motif ?: null], Api::token());
        }

        $this->rejectingId = null;
        $this->motif = '';
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/marketplace/{$id}", Api::token());
    }

    public function render()
    {
        $all = collect(Api::get('/admin/marketplace', [], Api::token())['listings'] ?? []);

        $compteurs = [
            'en_attente' => $all->where('statut', 'en_attente')->count(),
            'approuve' => $all->where('statut', 'approuve')->count(),
            'refuse' => $all->where('statut', 'refuse')->count(),
            'tous' => $all->count(),
        ];

        $listings = $this->filtre === 'tous'
            ? $all
            : $all->where('statut', $this->filtre)->values();

        return view('livewire.admin.marketplace', [
            'listings' => $listings,
            'compteurs' => $compteurs,
        ]);
    }
}
