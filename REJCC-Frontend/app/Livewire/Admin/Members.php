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

    public int $page = 1;

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
        $this->page = 1;
    }

    public function setFiltreStatut(string $filtre): void
    {
        $this->filtreStatut = $filtre;
        $this->page = 1;
    }

    // Toute recherche/changement de période revient à la première page.
    public function updatedRecherche(): void
    {
        $this->page = 1;
    }

    public function updatedPeriode(): void
    {
        $this->page = 1;
    }

    public function gotoPage(int $p): void
    {
        $this->page = max(1, $p);
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

    public function toggleStatut(int $id, bool $actif): void
    {
        // L'état courant est passé par la vue : plus besoin de charger toute
        // la liste (indispensable maintenant que la liste est paginée).
        Api::put("/admin/members/{$id}", ['is_active' => ! $actif], Api::token());
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/members/{$id}", Api::token());
    }

    public function render()
    {
        // Recherche, filtres, tri et pagination sont faits côté serveur
        // (indispensable à l'échelle : plusieurs milliers de membres).
        $params = ['page' => $this->page];
        if (trim($this->recherche) !== '') {
            $params['q'] = trim($this->recherche);
        }
        if ($this->filtreRole !== 'tous') {
            $params['role'] = $this->filtreRole;
        }
        if ($this->filtreStatut !== 'tous') {
            $params['statut'] = $this->filtreStatut;
        }
        if ($this->periode !== 'toutes') {
            $params['periode'] = $this->periode;
        }

        $result = Api::get('/admin/members', $params, Api::token());
        $meta = $result['meta'] ?? [];

        $membres = Collection::make($result['members'] ?? [])
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
            ]);

        return view('livewire.admin.members', [
            'membres' => $membres,
            'total' => $meta['total'] ?? $membres->count(),
            'meta' => $meta,
            'sectionsDisponibles' => AdminSections::SECTIONS,
        ]);
    }
}
