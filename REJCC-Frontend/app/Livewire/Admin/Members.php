<?php

namespace App\Livewire\Admin;

use App\Support\AdminSections;
use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Members extends Component
{
    // ── Filtres ──────────────────────────────────────────────────────────
    public string $filtreRole = 'tous';

    public string $filtreStatut = 'tous';

    public string $periode = 'toutes';

    public string $recherche = '';

    // ── Dossier membre (Voir) ────────────────────────────────────────────
    public ?int $voirId = null;

    public array $dossier = [];

    // ── Modification ─────────────────────────────────────────────────────
    public ?int $editingId = null;

    public string $prenom = '';

    public string $nom = '';

    public string $telephone = '';

    public string $ville = '';

    public string $role = 'member';

    public bool $accesComplet = true;

    public array $sections = [];

    // ── QR code ──────────────────────────────────────────────────────────
    public ?int $qrId = null;

    public array $qrData = [];

    public function setFiltreRole(string $filtre): void
    {
        $this->filtreRole = $filtre;
    }

    public function setFiltreStatut(string $filtre): void
    {
        $this->filtreStatut = $filtre;
    }

    public function voir(int $id): void
    {
        if ($this->voirId === $id) {
            $this->voirId = null;
            $this->dossier = [];

            return;
        }

        $result = Api::get("/admin/members/{$id}", [], Api::token());
        if (! ($result['ok'] ?? false)) {
            return;
        }

        $this->editingId = null;
        $this->qrId = null;
        $this->voirId = $id;
        $this->dossier = [
            'member' => $result['member'],
            'application' => $result['application'],
            'formations' => $result['formations'] ?? [],
        ];
    }

    public function openEdit(int $id): void
    {
        $result = Api::get("/admin/members/{$id}", [], Api::token());
        if (! ($result['ok'] ?? false)) {
            return;
        }

        $m = $result['member'];
        $this->voirId = null;
        $this->qrId = null;
        $this->editingId = $id;
        $this->prenom = $m['prenom'] ?? '';
        $this->nom = $m['nom'] ?? '';
        $this->telephone = $m['telephone'] ?? '';
        $this->ville = $m['ville'] ?? '';
        $this->role = $m['role'];
        $this->accesComplet = ($m['permissions'] ?? null) === null;
        $this->sections = $m['permissions'] ?? [];
        $this->resetValidation();
    }

    public function closeEdit(): void
    {
        $this->editingId = null;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'telephone' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'ville' => 'nullable|string|max:80',
            'role' => 'required|in:member,mentor,admin',
        ]);

        $data = [
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'telephone' => $this->telephone ?: null,
            'ville' => $this->ville ?: null,
            'role' => $this->role,
            'permissions' => ($this->role === 'admin' && ! $this->accesComplet) ? array_values($this->sections) : null,
        ];

        Api::put("/admin/members/{$this->editingId}", $data, Api::token());

        $this->closeEdit();
    }

    public function qr(int $id): void
    {
        if ($this->qrId === $id) {
            $this->qrId = null;

            return;
        }

        $result = Api::get("/admin/members/{$id}", [], Api::token());
        if (! ($result['ok'] ?? false)) {
            return;
        }

        $m = $result['member'];
        $this->voirId = null;
        $this->editingId = null;
        $this->qrId = $id;
        $this->qrData = [
            'nom' => trim(($m['prenom'] ?? '').' '.($m['nom'] ?? '')),
            'code' => $m['code'],
            'reference' => $m['reference'],
            'url' => url('/carte/'.$m['code']),
        ];
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
        $tous = Collection::make(Api::get('/admin/members', [], Api::token())['members'] ?? [])
            ->when($this->recherche !== '', function ($c) {
                $q = mb_strtolower($this->recherche);

                return $c->filter(fn ($m) => str_contains(mb_strtolower(($m['prenom'] ?? '').' '.($m['nom'] ?? '').' '.($m['email'] ?? '')), $q));
            })
            ->when($this->filtreRole !== 'tous', fn ($c) => $c->where('role', $this->filtreRole))
            ->when($this->filtreStatut === 'actifs', fn ($c) => $c->filter(fn ($m) => $m['is_active'] ?? true))
            ->when($this->filtreStatut === 'suspendus', fn ($c) => $c->filter(fn ($m) => ! ($m['is_active'] ?? true)))
            ->when($this->periode !== 'toutes', function ($c) {
                $depuis = match ($this->periode) {
                    '30j' => now()->subDays(30),
                    '90j' => now()->subDays(90),
                    'annee' => now()->startOfYear(),
                    default => null,
                };

                return $depuis ? $c->filter(fn ($m) => Carbon::parse($m['created_at'])->gte($depuis)) : $c;
            })
            ->map(fn ($m) => [
                'id' => $m['id'],
                'nom' => trim(($m['prenom'] ?? '').' '.($m['nom'] ?? '')) ?: $m['email'],
                'email' => $m['email'],
                'telephone' => $m['telephone'] ?: '—',
                'domaine' => $m['domaines_formation'] ?: '—',
                'ville' => $m['ville'] ?? null,
                'numero' => $m['numero'] ?? '—',
                'role' => $m['role'],
                'restreint' => $m['role'] === 'admin' && ($m['permissions'] ?? null) !== null,
                'actif' => (bool) ($m['is_active'] ?? true),
                'depuis' => Carbon::parse($m['created_at'])->translatedFormat('j M Y'),
                'initiales' => mb_strtoupper(mb_substr($m['prenom'] ?? $m['email'], 0, 1).mb_substr($m['nom'] ?? '', 0, 1)),
            ])
            // Classement : administrateurs, puis mentors, puis membres, puis par nom.
            ->sortBy([
                fn ($m) => ['admin' => 0, 'mentor' => 1, 'member' => 2][$m['role']] ?? 3,
                fn ($m) => mb_strtolower($m['nom']),
            ])
            ->values();

        return view('livewire.admin.members', [
            'membres' => $tous,
            'total' => $tous->count(),
            'sectionsDisponibles' => AdminSections::SECTIONS,
        ]);
    }
}
