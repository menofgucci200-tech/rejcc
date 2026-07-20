<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Module « Inscriptions » : événements à inscription publique par QR code
 * (lancement + événements à venir). Estimation des participants, fermeture
 * des inscriptions, base de données exportable.
 */
#[Layout('layouts.admin-light')]
class Inscriptions extends Component
{
    // ── Formulaire création / édition ────────────────────────────────────
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $date = '';

    public string $location = '';

    public string $description = '';

    public ?int $capacity = null;

    public bool $is_open = true;

    // ── Panneau détail (QR / participants) ───────────────────────────────
    public ?int $detailId = null;

    public string $detailTab = 'qr'; // qr | participants

    // ── Participants (pagination + recherche) ────────────────────────────
    public string $q = '';

    public int $page = 1;

    public ?string $message = null;

    protected function events(): Collection
    {
        return Collection::make(Api::get('/admin/registration-events', [], Api::token())['events'] ?? []);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'date', 'location', 'description', 'capacity']);
        $this->is_open = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $e = $this->events()->firstWhere('id', $id);
        if (! $e) {
            return;
        }

        $this->editingId = $id;
        $this->title = $e['title'];
        $this->date = ($e['starts_at'] ?? null) ? Carbon::parse($e['starts_at'])->format('Y-m-d\TH:i') : '';
        $this->location = $e['location'] ?? '';
        $this->description = $e['description'] ?? '';
        $this->capacity = $e['capacity'] ?? null;
        $this->is_open = (bool) ($e['is_open'] ?? true);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|min:3|max:160',
            'date' => 'nullable|date',
            'location' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:2000',
            'capacity' => 'nullable|integer|min:1|max:1000000',
        ], [
            'title.required' => "Donnez un titre à l'événement.",
            'title.min' => 'Le titre est trop court.',
            'capacity.integer' => 'La capacité doit être un nombre.',
            'capacity.min' => 'La capacité doit être d\'au moins 1.',
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'location' => $this->location ?: null,
            'starts_at' => $this->date ?: null,
            'capacity' => $this->capacity ?: null,
            'is_open' => $this->is_open,
        ];

        $token = Api::token();
        if ($this->editingId) {
            $result = Api::put("/admin/registration-events/{$this->editingId}", $data, $token);
        } else {
            $result = Api::post('/admin/registration-events', $data, $token);
        }

        if (! ($result['ok'] ?? false)) {
            $this->addError('title', $result['message'] ?? 'L\'enregistrement a échoué.');

            return;
        }

        $this->closeForm();
        $this->message = 'Événement enregistré.';
    }

    public function toggle(int $id): void
    {
        Api::post("/admin/registration-events/{$id}/toggle", [], Api::token());
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/registration-events/{$id}", Api::token());
        if ($this->detailId === $id) {
            $this->detailId = null;
        }
    }

    public function openDetail(int $id, string $tab = 'qr'): void
    {
        if ($this->detailId === $id && $this->detailTab === $tab) {
            $this->detailId = null;

            return;
        }

        $this->detailId = $id;
        $this->detailTab = $tab;
        $this->reset(['q', 'page']);
    }

    public function setTab(string $tab): void
    {
        $this->detailTab = $tab;
        $this->reset(['q', 'page']);
    }

    public function updatedQ(): void
    {
        $this->page = 1;
    }

    public function gotoPage(int $p): void
    {
        $this->page = max(1, $p);
    }

    public function render()
    {
        $events = $this->events()->map(function (array $e) {
            $e['date_label'] = ($e['starts_at'] ?? null)
                ? Carbon::parse($e['starts_at'])->locale('fr')->translatedFormat('j F Y · H\hi')
                : null;
            $e['url'] = url('/participer/'.$e['slug']);
            $e['percent'] = ($e['capacity'] ?? null) ? min(100, (int) round(($e['count'] / $e['capacity']) * 100)) : null;

            return $e;
        });

        // Participants du panneau ouvert (onglet participants).
        $participants = collect();
        $pMeta = [];
        if ($this->detailId && $this->detailTab === 'participants') {
            $params = ['page' => $this->page];
            if (trim($this->q) !== '') {
                $params['q'] = trim($this->q);
            }
            $res = Api::get("/admin/registration-events/{$this->detailId}/participants", $params, Api::token());
            $participants = Collection::make($res['participants'] ?? [])->map(function ($p) {
                $p['date_label'] = Carbon::parse($p['created_at'])->format('d/m/Y H:i');

                return $p;
            });
            $pMeta = $res['meta'] ?? [];
        }

        return view('livewire.admin.inscriptions', [
            'events' => $events,
            'participants' => $participants,
            'meta' => $pMeta,
        ]);
    }
}
