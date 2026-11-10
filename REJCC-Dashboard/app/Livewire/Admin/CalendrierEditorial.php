<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Calendrier éditorial de la Communication : grille du mois (liste par jour
 * sur téléphone), publications prévues, tours de rôle par blocs de jours,
 * « Marquer publié » en un clic, import du calendrier Excel et export CSV.
 */
#[Layout('layouts.admin-light')]
class CalendrierEditorial extends Component
{
    use WithFileUploads;

    #[Url]
    public string $mois = '';

    #[Url]
    public string $vue = 'grille';

    #[Url(as: 'responsable')]
    public string $fResponsable = '';

    #[Url(as: 'canal')]
    public string $fCanal = '';

    #[Url(as: 'statut')]
    public string $fStatut = '';

    /** '' | publication | tours | import */
    public string $panneau = '';

    // ── Formulaire de publication ───────────────────────────────────────
    public ?int $editId = null;

    public string $date = '';

    public string $heure = '';

    public string $titre = '';

    public string $type = 'autre';

    public string $format = '';

    /** @var list<string> */
    public array $canaux = [];

    public string $responsableId = '';

    public string $responsableLibre = '';

    public string $notes = '';

    public string $statut = 'prevu';

    public string $liens = '';

    public ?int $demandeId = null;

    // ── Tours de rôle ───────────────────────────────────────────────────
    public string $tourDu = '';

    public string $tourAu = '';

    public string $tourResponsable = '';

    public string $tourLibre = '';

    /** @var list<string> ids des membres choisis pour l'assistant, dans l'ordre */
    public array $membresChoisis = [];

    public string $membresLibres = '';

    /** @var list<array> proposition de l'assistant, modifiable avant l'enregistrement */
    public array $proposition = [];

    // ── Import ──────────────────────────────────────────────────────────
    public $fichier = null;

    public ?array $apercu = null;

    public ?array $resultatImport = null;

    public ?string $message = null;

    public ?string $erreur = null;

    public function mount(): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->mois)) {
            $this->mois = now()->format('Y-m');
        }
        if (! in_array($this->vue, ['grille', 'liste'], true)) {
            $this->vue = 'grille';
        }
        // Ouverture directe d'une publication (lien depuis l'espace du département).
        if ($id = (int) request()->query('publication')) {
            $this->modifier($id);
        }
    }

    public function changerMois(int $delta): void
    {
        $this->mois = Carbon::createFromFormat('Y-m-d', $this->mois.'-01')->addMonthsNoOverflow($delta)->format('Y-m');
        $this->proposition = [];
    }

    public function moisCourant(): void
    {
        $this->mois = now()->format('Y-m');
    }

    public function ouvrir(string $panneau): void
    {
        $this->panneau = $this->panneau === $panneau ? '' : $panneau;
        $this->message = $this->erreur = null;
        if ($panneau === 'publication' && $this->panneau === 'publication' && ! $this->editId) {
            $this->viderFormulaire();
        }
    }

    // ── Publications ────────────────────────────────────────────────────

    /** Nouvelle publication, éventuellement à une date (clic sur un jour de la grille). */
    public function nouvelle(?string $date = null): void
    {
        $this->viderFormulaire();
        $this->date = $date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : now()->toDateString();
        $this->panneau = 'publication';
        $this->message = $this->erreur = null;
    }

    private function viderFormulaire(): void
    {
        $this->reset(['editId', 'heure', 'titre', 'format', 'canaux', 'responsableId', 'responsableLibre', 'notes', 'liens', 'demandeId']);
        $this->type = 'autre';
        $this->statut = 'prevu';
        $this->date = $this->date ?: now()->toDateString();
        $this->resetErrorBag();
    }

    /** Canaux pré-cochés selon le plan de diffusion de la Com (modifiables ensuite). */
    public function updatedType(string $type): void
    {
        $plan = $this->donnees()['plan'] ?? [];
        if (isset($plan[$type]) && $plan[$type] !== []) {
            $this->canaux = $plan[$type];
        }
    }

    public function modifier(int $id): void
    {
        $r = Api::get("/admin/communication/publications/{$id}", [], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = 'Publication introuvable.';

            return;
        }
        $p = $r['publication'];
        $this->editId = $p['id'];
        $this->date = $p['date'];
        $this->heure = (string) $p['heure'];
        $this->titre = $p['titre'];
        $this->type = $p['type'];
        $this->format = (string) $p['format'];
        $this->canaux = $p['canaux'];
        $this->responsableId = (string) ($p['responsable_id'] ?? '');
        $this->responsableLibre = (string) ($p['responsable_libre'] ?? '');
        $this->notes = (string) $p['notes'];
        $this->statut = $p['statut'];
        $this->liens = implode("\n", $p['liens']);
        $this->demandeId = $p['demande_id'];
        $this->panneau = 'publication';
        $this->message = $this->erreur = null;
        $this->resetErrorBag();
    }

    public function enregistrer(): void
    {
        $this->message = $this->erreur = null;
        $donnees = [
            'date' => $this->date,
            'heure' => $this->heure ?: null,
            'titre' => trim($this->titre),
            'type' => $this->type,
            'format' => trim($this->format) ?: null,
            'canaux' => array_values($this->canaux),
            'responsable_id' => $this->responsableId !== '' ? (int) $this->responsableId : null,
            'responsable' => $this->responsableId === '' ? (trim($this->responsableLibre) ?: null) : null,
            'notes' => trim($this->notes) ?: null,
            'statut' => $this->statut,
            'liens' => array_values(array_filter(array_map('trim', preg_split('/[\r\n\s]+/', $this->liens)))),
        ];
        if (! $this->editId && $this->demandeId) {
            $donnees['demande_id'] = $this->demandeId;
        }
        $r = $this->editId
            ? Api::put("/admin/communication/publications/{$this->editId}", $donnees, Api::token())
            : Api::post('/admin/communication/publications', $donnees, Api::token());

        if (! ($r['ok'] ?? false)) {
            $this->erreur = collect($r['errors'] ?? [])->flatten()->first() ?? ($r['message'] ?? "L'enregistrement a échoué.");

            return;
        }
        $this->message = $this->editId ? 'Publication modifiée.' : 'Publication ajoutée au calendrier.';
        // Le mois affiché suit la publication enregistrée.
        $this->mois = substr($r['publication']['date'], 0, 7);
        $this->viderFormulaire();
        $this->panneau = '';
        AdminNav::oublier();
    }

    public function supprimer(int $id): void
    {
        $r = Api::delete("/admin/communication/publications/{$id}", Api::token());
        if ($this->editId === $id) {
            $this->viderFormulaire();
            $this->panneau = '';
        }
        $this->dispatch('rj-toast', type: ($r['ok'] ?? false) ? 'success' : 'error', message: ($r['ok'] ?? false) ? 'Publication supprimée.' : ($r['message'] ?? 'Suppression impossible.'));
        AdminNav::oublier();
    }

    /** « Marquer publié » en un clic, avec le lien de la publication collé (facultatif). */
    public function marquerPublie(int $id, ?string $lien = null): void
    {
        $lien = trim((string) $lien);
        $r = Api::post("/admin/communication/publications/{$id}/publie", $lien !== '' ? ['lien' => $lien] : [], Api::token());
        $this->dispatch('rj-toast', type: ($r['ok'] ?? false) ? 'success' : 'error', message: ($r['ok'] ?? false)
            ? 'Bravo, publication marquée « publié ».'
            : (collect($r['errors'] ?? [])->flatten()->first() ?? ($r['message'] ?? 'Action impossible.')));
        AdminNav::oublier();
    }

    public function changerStatut(int $id, string $statut): void
    {
        $r = Api::put("/admin/communication/publications/{$id}", ['statut' => $statut], Api::token());
        $this->dispatch('rj-toast', type: ($r['ok'] ?? false) ? 'success' : 'error', message: ($r['ok'] ?? false) ? 'Statut mis à jour.' : ($r['message'] ?? 'Action impossible.'));
        AdminNav::oublier();
    }

    public function dupliquer(): void
    {
        $r = Api::post('/admin/communication/calendrier/dupliquer', ['mois' => $this->mois], Api::token());
        $n = (int) ($r['copiees'] ?? 0);
        $this->message = ($r['ok'] ?? false) ? ($n ? "{$n} publication".($n > 1 ? 's' : '').' recopiée'.($n > 1 ? 's' : '').' du mois précédent, au statut « prévu ».' : 'Rien à recopier : le mois précédent est vide.') : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Duplication impossible.');
    }

    // ── Tours de rôle ───────────────────────────────────────────────────

    public function ajouterTour(): void
    {
        $this->erreur = null;
        $r = Api::post('/admin/communication/tours', [
            'du' => $this->tourDu, 'au' => $this->tourAu,
            'responsable_id' => $this->tourResponsable !== '' ? (int) $this->tourResponsable : null,
            'responsable' => $this->tourResponsable === '' ? (trim($this->tourLibre) ?: null) : null,
        ], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = collect($r['errors'] ?? [])->flatten()->first() ?? ($r['message'] ?? 'Tour non enregistré.');

            return;
        }
        $this->message = 'Tour ajouté.';
        $this->reset(['tourDu', 'tourAu', 'tourResponsable', 'tourLibre']);
    }

    public function supprimerTour(int $id): void
    {
        $r = Api::delete("/admin/communication/tours/{$id}", Api::token());
        $this->message = ($r['ok'] ?? false) ? 'Tour supprimé.' : null;
    }

    /** Assistant « Générer les tours du mois » : proposition à ajuster avant l'enregistrement. */
    public function proposerTours(): void
    {
        $this->erreur = null;
        $membres = collect($this->membresChoisis)->filter()->map(fn ($id) => ['id' => (int) $id])
            ->merge(collect(preg_split('/[,;\n]+/', $this->membresLibres))->map('trim')->filter()->map(fn ($n) => ['nom' => $n]))
            ->values()->all();
        $r = Api::post('/admin/communication/tours/proposer', ['mois' => $this->mois, 'membres' => $membres], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Choisissez au moins un membre.';

            return;
        }
        // Une seule liste de choix : « id » pour un compte, « libre:Nom » pour un nom libre.
        $this->proposition = collect($r['tours'])->map(fn ($t) => [
            'du' => $t['du'], 'au' => $t['au'],
            'choix' => $t['responsable_id'] ? (string) $t['responsable_id'] : 'libre:'.$t['responsable'],
        ])->all();
    }

    public function enregistrerTours(): void
    {
        $tours = collect($this->proposition)->map(function ($t) {
            $libre = str_starts_with((string) $t['choix'], 'libre:');

            return ['du' => $t['du'], 'au' => $t['au'],
                'responsable_id' => $libre || $t['choix'] === '' ? null : (int) $t['choix'],
                'responsable' => $libre ? substr($t['choix'], 6) : null];
        })->all();
        $r = Api::post('/admin/communication/tours/lot', ['mois' => $this->mois, 'tours' => $tours], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = collect($r['errors'] ?? [])->flatten()->first() ?? ($r['message'] ?? 'Tours non enregistrés.');

            return;
        }
        $this->proposition = [];
        $this->message = count($tours).' tours enregistrés pour le mois (ils remplacent les tours qui commençaient ce mois-ci).';
    }

    // ── Import ──────────────────────────────────────────────────────────

    public function updatedFichier(): void
    {
        $this->apercu = $this->resultatImport = null;
        $this->erreur = null;
        $this->envoyerFichier(apercu: true);
    }

    public function importer(): void
    {
        $this->envoyerFichier(apercu: false);
    }

    private function envoyerFichier(bool $apercu): void
    {
        $this->validate(['fichier' => 'required|file|max:2048|extensions:csv,txt,xlsx'], [
            'fichier.extensions' => 'Envoyez un fichier .csv (Excel « CSV UTF-8 ») ou .xlsx.',
            'fichier.max' => 'Fichier trop lourd (2 Mo au plus).',
        ]);
        $r = Api::post('/admin/communication/publications/import', [
            'fichier' => base64_encode(file_get_contents($this->fichier->getRealPath())),
            'nom' => $this->fichier->getClientOriginalName(),
            'apercu' => $apercu,
        ], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Import impossible.';
            $this->apercu = null;

            return;
        }
        if ($apercu) {
            $this->apercu = $r;

            return;
        }
        $this->resultatImport = $r;
        $this->apercu = null;
        $this->fichier = null;
        $n = (int) $r['importees'];
        $this->message = "{$n} publication".($n > 1 ? 's' : '').' importée'.($n > 1 ? 's' : '').'.';
        // Affiche le mois de la première ligne importée.
        AdminNav::oublier();
    }

    public function annulerImport(): void
    {
        $this->reset(['fichier', 'apercu', 'resultatImport']);
    }

    // ── Données ─────────────────────────────────────────────────────────

    private ?array $cache = null;

    private function donnees(): array
    {
        return $this->cache ??= Api::get('/admin/communication/calendrier', array_filter([
            'mois' => $this->mois, 'responsable' => $this->fResponsable, 'canal' => $this->fCanal, 'statut' => $this->fStatut,
        ]), Api::token());
    }

    public function render()
    {
        $d = $this->donnees();
        $debut = Carbon::createFromFormat('Y-m-d', $this->mois.'-01')->startOfDay();
        $publications = collect($d['publications'] ?? []);
        $tours = collect($d['tours'] ?? []);
        $equipe = collect($d['equipe'] ?? []);

        // Couleur stable par responsable de tour (pastilles de la grille).
        $palette = ['#4F6FBF', '#1C8F4C', '#B7791F', '#7C3AED', '#0E7490', '#BE185D', '#031D59', '#64748B']; // sans rouge : réservé aux retards
        $couleurs = $tours->pluck('responsable')->filter()->unique()->values()->mapWithKeys(fn ($n, $i) => [$n => $palette[$i % count($palette)]]);
        $tourDuJour = function (string $jour) use ($tours) {
            return $tours->filter(fn ($t) => $t['du'] <= $jour && $t['au'] >= $jour)->sortByDesc('id')->first();
        };

        return view('livewire.admin.calendrier-editorial', [
            'ok' => $d['ok'] ?? false,
            'debut' => $debut,
            'aujourdhui' => $d['aujourdhui'] ?? now()->toDateString(),
            'publications' => $publications,
            'parJour' => $publications->groupBy('date'),
            'tours' => $tours,
            'tourDuJour' => $tourDuJour,
            'couleurs' => $couleurs,
            'tourEnCours' => $d['tour_en_cours'] ?? null,
            'retards' => $d['retards'] ?? [],
            'equipe' => $equipe,
            'types' => $d['types'] ?? [],
            'plan' => $d['plan'] ?? [],
            'canauxListe' => $d['canaux'] ?? [],
            'formats' => $d['formats'] ?? [],
            'statuts' => $d['statuts'] ?? [],
            'filtres' => $this->fResponsable !== '' || $this->fCanal !== '' || $this->fStatut !== '',
        ]);
    }
}
