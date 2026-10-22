<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Formations live (département Formation) : programmer une session en visio
 * rattachée à une formation, la lancer, suivre les inscrits. Chaque session
 * apparaît dans le calendrier des membres, qui sont prévenus et reçoivent un
 * rappel la veille et le jour même.
 */
#[Layout('layouts.admin-light')]
class FormationsLive extends Component
{
    #[Url(as: 'mois', except: '')]
    public string $mois = '';

    public ?string $jour = null;

    // ── Formulaire ───────────────────────────────────────────────────────
    public bool $formulaire = false;

    public ?int $editionId = null;

    public string $titre = '';

    public string $formationId = '';

    public string $animateur = '';

    public string $date = '';

    public string $heure = '18:00';

    public int $duree = 60;

    public string $description = '';

    public string $visio = 'auto';

    public string $lienVisio = '';

    public string $places = '';

    public bool $annoncer = true;

    public ?string $erreur = null;

    public ?string $message = null;

    // ── Inscrits ─────────────────────────────────────────────────────────
    public ?int $inscritsDe = null;

    public array $inscrits = [];

    public function mount(): void
    {
        $this->mois = $this->mois ?: now()->format('Y-m');
    }

    public function moisPrecedent(): void
    {
        $this->mois = Carbon::createFromFormat('Y-m-d', $this->mois.'-01')->subMonth()->format('Y-m');
        $this->jour = null;
    }

    public function moisSuivant(): void
    {
        $this->mois = Carbon::createFromFormat('Y-m-d', $this->mois.'-01')->addMonth()->format('Y-m');
        $this->jour = null;
    }

    public function choisirJour(string $date): void
    {
        $this->jour = $this->jour === $date ? null : $date;
    }

    public function nouvelle(?string $date = null): void
    {
        $this->reset(['editionId', 'titre', 'formationId', 'animateur', 'description', 'lienVisio', 'places', 'erreur', 'message']);
        $this->date = $date ?? now()->addDay()->toDateString();
        $this->heure = '18:00';
        $this->duree = 60;
        $this->visio = 'auto';
        $this->annoncer = true;
        $this->formulaire = true;
    }

    public function modifier(int $id): void
    {
        $s = collect($this->donnees()['sessions'] ?? [])->firstWhere('id', $id);
        if (! $s) {
            return;
        }
        $debut = Carbon::parse($s['starts_at']);
        $this->editionId = $id;
        $this->titre = $s['title'];
        $this->formationId = (string) ($s['formation_id'] ?? '');
        $this->animateur = (string) ($s['animateur'] ?? '');
        $this->description = (string) ($s['description'] ?? '');
        $this->date = $debut->toDateString();
        $this->heure = $debut->format('H:i');
        $this->duree = (int) ($s['duree'] ?? 60);
        $this->visio = $s['visio_auto'] ? 'auto' : 'lien';
        $this->lienVisio = $s['visio_auto'] ? '' : (string) $s['lien_visio'];
        $this->places = $s['capacity'] ? (string) $s['capacity'] : '';
        $this->annoncer = ! $s['annonce'];
        $this->erreur = null;
        $this->message = null;
        $this->formulaire = true;
    }

    public function fermer(): void
    {
        $this->formulaire = false;
        $this->erreur = null;
    }

    public function enregistrer(): void
    {
        $this->validate([
            'titre' => 'required|string|min:3|max:160',
            'date' => 'required|date_format:Y-m-d',
            'heure' => 'required|date_format:H:i',
            'duree' => 'required|integer|min:15|max:600',
            'visio' => 'required|in:auto,lien',
            'lienVisio' => 'nullable|required_if:visio,lien|url',
            'places' => 'nullable|integer|min:1',
        ], [
            'lienVisio.required_if' => 'Collez le lien de la visio (Google Meet, Zoom, Teams…).',
        ], ['titre' => 'titre', 'lienVisio' => 'lien de la visio', 'duree' => 'durée', 'places' => 'nombre de places']);

        $data = [
            'title' => $this->titre,
            'formation_id' => $this->formationId !== '' ? (int) $this->formationId : null,
            'animateur' => $this->animateur ?: null,
            'description' => $this->description ?: null,
            'starts_at' => $this->date.' '.$this->heure,
            'duree' => $this->duree,
            'visio' => $this->visio,
            'lien_visio' => $this->visio === 'lien' ? $this->lienVisio : null,
            'capacity' => $this->places !== '' ? (int) $this->places : null,
            'annoncer' => $this->annoncer,
        ];

        $r = $this->editionId
            ? Api::put("/admin/formations-live/{$this->editionId}", $data, Api::token())
            : Api::post('/admin/formations-live', $data, Api::token());

        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Enregistrement impossible.';

            return;
        }

        $this->formulaire = false;
        $this->mois = Carbon::parse($r['session']['starts_at'])->format('Y-m');
        $this->message = ($this->editionId ? 'Session mise à jour.' : 'Session programmée : elle apparaît dans le calendrier des membres.')
            .(($n = (int) ($r['annonces'] ?? 0)) > 0 ? ' '.$n.($n > 1 ? ' membres prévenus.' : ' membre prévenu.') : '');
    }

    public function annuler(int $id): void
    {
        $r = Api::post("/admin/formations-live/{$id}/annuler", [], Api::token());
        $this->message = ($r['ok'] ?? false)
            ? 'Session annulée. Les inscrits ont été prévenus.'
            : ($r['message'] ?? 'Annulation impossible.');
    }

    public function voirInscrits(int $id): void
    {
        if ($this->inscritsDe === $id) {
            $this->inscritsDe = null;

            return;
        }
        $this->inscritsDe = $id;
        $this->inscrits = Api::get("/admin/formations-live/{$id}/inscrits", [], Api::token())['inscrits'] ?? [];
    }

    private function donnees(): array
    {
        return once(fn () => Api::get('/admin/formations-live', [], Api::token()));
    }

    public function render()
    {
        $r = $this->donnees();
        $sessions = collect($r['sessions'] ?? [])->map(function ($s) {
            $s['debut'] = Carbon::parse($s['starts_at'])->locale('fr');
            $s['fin'] = $s['ends_at'] ? Carbon::parse($s['ends_at'])->locale('fr') : null;

            return $s;
        });

        $actives = $sessions->where('statut', 'publie');
        $mois = Carbon::createFromFormat('Y-m-d', $this->mois.'-01')->locale('fr');
        $jours = $actives->filter(fn ($s) => $s['debut']->isSameMonth($mois))->groupBy(fn ($s) => $s['debut']->toDateString());
        $cells = array_fill(0, $mois->copy()->startOfMonth()->dayOfWeekIso - 1, null);
        for ($d = 1; $d <= $mois->daysInMonth; $d++) {
            $cells[] = $mois->copy()->day($d)->toDateString();
        }

        $aVenir = $sessions->filter(fn ($s) => ! $s['passe'])
            ->when($this->jour, fn ($c) => $c->filter(fn ($s) => $s['debut']->toDateString() === $this->jour))
            ->values();

        return view('livewire.admin.formations-live', [
            'formations' => $r['formations'] ?? [],
            'enDirect' => $actives->filter(fn ($s) => $s['en_cours'] || ($s['debut']->isToday() && ! $s['passe']))->values(),
            'aVenir' => $aVenir,
            'passees' => $sessions->filter(fn ($s) => $s['passe'])->sortByDesc('starts_at')->take(10)->values(),
            'cells' => $cells,
            'jours' => $jours->map->count()->all(),
            'moisLabel' => ucfirst($mois->isoFormat('MMMM YYYY')),
            'aujourdhui' => now()->toDateString(),
        ]);
    }
}
