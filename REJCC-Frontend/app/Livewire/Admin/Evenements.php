<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\Api;
use App\Support\CategoryPalette;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Administration des événements : brouillon / publication (avec annonce aux
 * membres), règles d'inscription, inscription publique par QR code pour les
 * non-membres (questions personnalisées), liste unique des inscrits (membres
 * et invités), export, message aux inscrits, report et annulation prévenus.
 */
#[Layout('layouts.admin-light')]
class Evenements extends Component
{
    use HandlesMedia;

    #[Url(except: 'avenir')]
    public string $filtre = 'avenir';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    public ?string $message = null;

    public ?string $erreur = null;

    // ── Formulaire ───────────────────────────────────────────────────────
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $statut = 'brouillon';

    public bool $dejaAnnonce = false;

    public int $nbInscritsForm = 0;

    public string $title = '';

    public string $category = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public string $timeLabel = '';

    public bool $enLigne = false;

    public string $location = '';

    public string $lienVisio = '';

    public ?int $capacity = null;

    public string $dateLimite = '';

    public bool $inscriptionsOuvertes = true;

    public bool $reserveAbonnes = false;

    public bool $inscriptionPublique = false;

    /** Questions du formulaire public (label, type, required, options en texte multi-ligne). */
    public array $champs = [];

    public string $excerpt = '';

    public string $description = '';

    public bool $annoncer = true;

    // ── Panneau de détail ────────────────────────────────────────────────
    public ?int $detailId = null;

    public string $detailTab = 'inscrits'; // inscrits | qr | message

    public string $q = '';

    public ?int $ouvert = null;

    public string $texteMessage = '';

    // ── Annulation ───────────────────────────────────────────────────────
    public ?int $annulerId = null;

    public string $motif = '';

    public const FILTRES = ['avenir' => 'À venir', 'brouillons' => 'Brouillons', 'passes' => 'Passés', 'annules' => 'Annulés', 'tous' => 'Tous'];

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:160',
            'category' => 'required|string|min:2|max:60',
            'startsAt' => 'required|date',
            'endsAt' => 'nullable|date|after:startsAt',
            'timeLabel' => 'nullable|string|max:60',
            'location' => 'nullable|string|max:160',
            'lienVisio' => $this->enLigne ? 'required|url|max:500' : 'nullable',
            'excerpt' => 'nullable|string|max:300',
            'description' => 'nullable|string|max:3000',
            'capacity' => 'nullable|integer|min:1',
            'dateLimite' => 'nullable|date|before_or_equal:startsAt',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => "Donnez un titre à l'événement.",
            'category.required' => 'Indiquez une catégorie (Atelier, Forum, Networking…).',
            'startsAt.required' => 'Indiquez la date et l\'heure de début.',
            'endsAt.after' => 'La fin doit être après le début.',
            'lienVisio.required' => 'Indiquez le lien de connexion (Zoom, Meet…).',
            'lienVisio.url' => 'Le lien de connexion doit être une adresse complète (https://…).',
            'capacity.min' => 'La capacité doit être d\'au moins 1 place.',
            'dateLimite.before_or_equal' => "La date limite doit précéder le début de l'événement.",
        ];
    }

    protected function evenements(): Collection
    {
        return Collection::make(Api::get('/admin/events', [], Api::token())['events'] ?? []);
    }

    private function flash(?string $ok, ?string $ko = null): void
    {
        $this->message = $ok;
        $this->erreur = $ko;
    }

    public function setFiltre(string $f): void
    {
        $this->filtre = array_key_exists($f, self::FILTRES) ? $f : 'avenir';
        $this->detailId = null;
    }

    // ── Formulaire ───────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'category', 'startsAt', 'endsAt', 'timeLabel', 'enLigne', 'location', 'lienVisio',
            'capacity', 'dateLimite', 'reserveAbonnes', 'inscriptionPublique', 'champs', 'excerpt', 'description', 'dejaAnnonce', 'nbInscritsForm']);
        $this->statut = 'brouillon';
        $this->inscriptionsOuvertes = true;
        $this->annoncer = true;
        $this->clearMedia();
        $this->resetValidation();
        $this->flash(null);
        $this->detailId = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $e = $this->evenements()->firstWhere('id', $id);
        if (! $e) {
            return;
        }
        $date = fn ($v) => $v ? Carbon::parse($v)->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i') : '';

        $this->editingId = $e['id'];
        $this->statut = $e['statut'];
        $this->dejaAnnonce = ! empty($e['annonce_at']);
        $this->nbInscritsForm = (int) ($e['registrations_count'] ?? 0);
        $this->title = $e['title'];
        $this->category = $e['category'];
        $this->startsAt = $date($e['starts_at']);
        $this->endsAt = $date($e['ends_at'] ?? null);
        $this->timeLabel = $e['time_label'] ?? '';
        $this->enLigne = (bool) ($e['en_ligne'] ?? false);
        $this->location = $e['location'] ?? '';
        $this->lienVisio = $e['lien_visio'] ?? '';
        $this->capacity = $e['capacity'];
        $this->dateLimite = $date($e['date_limite'] ?? null);
        $this->inscriptionsOuvertes = (bool) ($e['inscriptions_ouvertes'] ?? true);
        $this->reserveAbonnes = (bool) ($e['reserve_abonnes'] ?? false);
        $this->inscriptionPublique = (bool) ($e['inscription_publique'] ?? false);
        $this->champs = collect($e['champs'] ?? [])->map(fn ($f) => [
            'label' => $f['label'] ?? '',
            'type' => $f['type'] ?? 'text',
            'required' => (bool) ($f['required'] ?? false),
            'options' => implode("\n", $f['options'] ?? []),
        ])->all();
        $this->excerpt = $e['excerpt'] ?? '';
        $this->description = $e['description'] ?? '';
        $this->annoncer = ! $this->dejaAnnonce;
        $this->fillMedia($e['image'] ?? null);
        $this->resetValidation();
        $this->flash(null);
        $this->detailId = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function addChamp(): void
    {
        $this->champs[] = ['label' => '', 'type' => 'text', 'required' => false, 'options' => ''];
    }

    public function removeChamp(int $i): void
    {
        unset($this->champs[$i]);
        $this->champs = array_values($this->champs);
    }

    public function updatedReserveAbonnes(bool $v): void
    {
        if ($v) {
            $this->inscriptionPublique = false;
        }
    }

    public function updatedInscriptionPublique(bool $v): void
    {
        if ($v) {
            $this->reserveAbonnes = false;
        }
    }

    /** Enregistre ; $statut = publie | brouillon (null : statut actuel). */
    public function save(?string $statut = null): void
    {
        $this->validate();

        $champs = [];
        foreach ($this->champs as $f) {
            if (trim($f['label'] ?? '') === '') {
                continue;
            }
            $c = ['label' => trim($f['label']), 'type' => $f['type'] ?? 'text', 'required' => (bool) ($f['required'] ?? false)];
            if ($c['type'] === 'select') {
                $c['options'] = array_values(array_filter(array_map('trim', explode("\n", $f['options'] ?? ''))));
            }
            $champs[] = $c;
        }

        $statut = in_array($statut, ['publie', 'brouillon'], true) ? $statut : $this->statut;
        $data = [
            'title' => trim($this->title),
            'category' => trim($this->category),
            'statut' => $statut === 'annule' ? null : $statut,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt ?: null,
            'time_label' => trim($this->timeLabel) ?: null,
            'en_ligne' => $this->enLigne,
            'location' => trim($this->location) ?: null,
            'lien_visio' => $this->enLigne ? trim($this->lienVisio) : null,
            'capacity' => $this->capacity ?: null,
            'date_limite' => $this->dateLimite ?: null,
            'inscriptions_ouvertes' => $this->inscriptionsOuvertes,
            'reserve_abonnes' => $this->reserveAbonnes,
            'inscription_publique' => $this->inscriptionPublique,
            'champs' => $champs,
            'excerpt' => trim($this->excerpt) ?: null,
            'description' => trim($this->description) ?: null,
            'image' => $this->mediaUrl ?: null,
            'annoncer' => $this->annoncer && ! $this->dejaAnnonce,
        ];

        $token = Api::token();
        $result = $this->editingId
            ? Api::put("/admin/events/{$this->editingId}", $data, $token)
            : Api::post('/admin/events', $data, $token);

        if (! ($result['ok'] ?? false)) {
            $this->erreur = $result['message'] ?? "L'enregistrement a échoué.";

            return;
        }

        $publie = ($result['event']['statut'] ?? '') === 'publie';
        $texte = $publie ? ($statut === 'publie' && $this->statut !== 'publie' ? 'Événement publié.' : 'Événement enregistré.') : 'Brouillon enregistré : il n\'est pas encore visible des membres.';
        if ($n = (int) ($result['annonces'] ?? 0)) {
            $texte .= " Annonce envoyée à {$n} membre".($n > 1 ? 's' : '').'.';
        }
        $texte .= $this->textePrevenus($result['prevenus'] ?? null, ' Changement signalé à');

        $this->closeForm();
        $this->flash($texte);
        // Affiche la liste où se trouve désormais l'événement, avec son détail ouvert.
        $passe = Carbon::parse($result['event']['ends_at'] ?? $result['event']['starts_at'])->isPast();
        $this->filtre = ! $publie ? 'brouillons' : ($passe ? 'passes' : 'avenir');
        $this->detailId = $result['event']['id'] ?? null;
        $this->detailTab = $this->inscriptionPublique ? 'qr' : 'inscrits';
    }

    private function textePrevenus(?array $p, string $debut): string
    {
        if (! $p || ($p['membres'] + $p['invites']) === 0) {
            return '';
        }
        $parts = [];
        if ($p['membres']) {
            $parts[] = $p['membres'].' membre'.($p['membres'] > 1 ? 's' : '').' (notification)';
        }
        if ($p['invites']) {
            $parts[] = $p['invites'].' invité'.($p['invites'] > 1 ? 's' : '').' (e-mail)';
        }

        return $debut.' '.implode(' et ', $parts).'.';
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/events/{$id}", Api::token());
        if ($this->detailId === $id) {
            $this->detailId = null;
        }
        $this->flash('Événement supprimé.');
    }

    // ── Annulation ───────────────────────────────────────────────────────

    public function ouvrirAnnulation(int $id): void
    {
        $this->annulerId = $id;
        $this->motif = '';
        $this->flash(null);
    }

    public function fermerAnnulation(): void
    {
        $this->annulerId = null;
    }

    public function confirmerAnnulation(): void
    {
        $r = Api::post("/admin/events/{$this->annulerId}/annuler", ['motif' => trim($this->motif)], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Une erreur est survenue.';

            return;
        }
        $this->annulerId = null;
        $this->flash('Événement annulé.'.$this->textePrevenus($r['prevenus'] ?? null, ' Prévenus :'));
    }

    public function retablir(int $id): void
    {
        $r = Api::post("/admin/events/{$id}/retablir", [], Api::token());
        ($r['ok'] ?? false)
            ? $this->flash('Événement rétabli et de nouveau visible.'.$this->textePrevenus($r['prevenus'] ?? null, ' Prévenus :'))
            : $this->flash(null, $r['message'] ?? 'Une erreur est survenue.');
    }

    // ── Détail : inscrits, QR, message ───────────────────────────────────

    public function openDetail(int $id, string $tab = 'inscrits'): void
    {
        if ($this->detailId === $id && $this->detailTab === $tab) {
            $this->detailId = null;

            return;
        }
        $this->detailId = $id;
        $this->detailTab = $tab;
        $this->q = '';
        $this->ouvert = null;
        $this->texteMessage = '';
        $this->flash(null);
    }

    public function basculer(int $id): void
    {
        $this->ouvert = $this->ouvert === $id ? null : $id;
    }

    public function retirerInscrit(int $id): void
    {
        Api::delete("/admin/events/{$this->detailId}/inscrits/{$id}", Api::token());
        $this->flash('Inscription retirée.');
    }

    public function envoyerMessage(): void
    {
        $r = Api::post("/admin/events/{$this->detailId}/message", ['message' => trim($this->texteMessage)], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->flash(null, $r['message'] ?? 'Une erreur est survenue.');

            return;
        }
        $this->texteMessage = '';
        $this->flash('Message envoyé.'.$this->textePrevenus($r['prevenus'] ?? null, ' Reçu par'));
    }

    public function render()
    {
        $tz = config('app.timezone');
        $tous = $this->evenements()->map(function (array $e) use ($tz) {
            $debut = Carbon::parse($e['starts_at'])->setTimezone($tz)->locale('fr');
            $e['jour'] = $debut->format('d');
            $e['mois'] = mb_strtoupper($debut->isoFormat('MMM'));
            $e['annee'] = $debut->format('Y');
            $e['date_label'] = ucfirst($debut->isoFormat('dddd D MMMM YYYY [à] HH[h]mm'));
            $e['tagColor'] = CategoryPalette::for($e['category'])['tag'];
            $e['url_public'] = url('/participer/'.$e['slug']);

            return $e;
        });

        $compteurs = [
            'avenir' => $tous->filter(fn ($e) => $e['statut'] === 'publie' && ! $e['passe'])->count(),
            'brouillons' => $tous->where('statut', 'brouillon')->count(),
            'passes' => $tous->filter(fn ($e) => $e['statut'] === 'publie' && $e['passe'])->count(),
            'annules' => $tous->where('statut', 'annule')->count(),
            'tous' => $tous->count(),
        ];
        $liste = match ($this->filtre) {
            'brouillons' => $tous->where('statut', 'brouillon')->sortBy('starts_at'),
            'passes' => $tous->filter(fn ($e) => $e['statut'] === 'publie' && $e['passe']),
            'annules' => $tous->where('statut', 'annule'),
            'tous' => $tous,
            default => $tous->filter(fn ($e) => $e['statut'] === 'publie' && ! $e['passe'])->sortBy('starts_at'),
        };
        $r = mb_strtolower(trim($this->recherche));
        if ($r !== '') {
            $liste = $liste->filter(fn ($e) => str_contains(mb_strtolower($e['title'].' '.$e['category'].' '.$e['location']), $r));
        }

        $detail = null;
        if ($this->detailId && $this->detailTab === 'inscrits') {
            $detail = Api::get("/admin/events/{$this->detailId}/inscrits", array_filter(['q' => trim($this->q)]), Api::token());
        }

        return view('livewire.admin.evenements', [
            'evenements' => $liste->values(),
            'compteurs' => $compteurs,
            'detail' => $detail,
            'categories' => $tous->pluck('category')->unique()->sort()->values(),
        ]);
    }
}
