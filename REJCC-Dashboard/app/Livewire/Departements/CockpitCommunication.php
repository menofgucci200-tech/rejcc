<?php

namespace App\Livewire\Departements;

use App\Support\AdminNav;
use App\Support\AdminSections;
use App\Support\Api;
use App\Support\StatistiquesSite;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Accueil de l'espace Communication : un écran qui dit quoi faire.
 * De haut en bas : la priorité du jour (une seule carte, un gros bouton),
 * quatre actions rapides, les résultats CathoImpact, quatre chiffres, puis
 * les publications d'aujourd'hui et de demain avec les demandes à traiter.
 * Tout se termine sur place : « Marquer publié », planifier une publication
 * (fenêtre modale), accepter, planifier ou refuser une demande.
 * Sources : /admin/communication/cockpit et /calendrier/aujourdhui.
 */
class CockpitCommunication extends Component
{
    // ── Fenêtre « Planifier une publication » ───────────────────────────
    public bool $modale = false;

    public string $pubDate = '';

    public string $pubTitre = '';

    public string $pubType = 'autre';

    /** @var list<string> */
    public array $pubCanaux = [];

    /** id d'un compte de l'équipe, ou vide : responsable du tour de rôle à cette date. */
    public string $pubResponsable = '';

    /** Listes du calendrier (types, canaux, plan de diffusion, équipe), chargées à la demande. */
    public array $options = [];

    // ── Action sur une demande (accueil) ────────────────────────────────
    public ?int $demandeId = null;

    /** '' | planifier | refuser */
    public string $demandeAction = '';

    public string $demDate = '';

    public string $demResponsable = '';

    public string $motif = '';

    public function mount(): void
    {
        abort_unless(AdminSections::allowed(session('api_user'), 'communication'), 403);
    }

    /** « Marquer publié » dans le bloc calendrier : les chiffres et la priorité se recalculent. */
    #[On('publication-publiee')]
    public function rafraichir(): void {}

    private function toast(array $r, string $succes, ?array $action = null): bool
    {
        $ok = (bool) ($r['ok'] ?? false);
        $this->dispatch('rj-toast', type: $ok ? 'success' : 'error', message: $ok ? $succes
            : (collect($r['errors'] ?? [])->flatten()->first() ?? ($r['message'] ?? 'Action impossible.')), action: $ok ? $action : null);
        if ($ok) {
            AdminNav::oublier(); // pastilles du menu et compteurs des actions rapides
        }

        return $ok;
    }

    /** Types, canaux, plan de diffusion et équipe : ceux du calendrier éditorial. */
    private function chargerOptions(): void
    {
        if ($this->options !== []) {
            return;
        }
        $r = Api::get('/admin/communication/calendrier', ['mois' => now()->format('Y-m')], Api::token());
        $this->options = [
            'types' => $r['types'] ?? [],
            'canaux' => $r['canaux'] ?? [],
            'plan' => $r['plan'] ?? [],
            'equipe' => collect($r['equipe'] ?? [])->map(fn ($m) => ['id' => $m['id'], 'nom' => $m['nom']])->all(),
        ];
    }

    // ── Publications ────────────────────────────────────────────────────

    /** Ouvre la fenêtre de planification, à une date (par défaut aujourd'hui). */
    public function ouvrirPlanification(?string $date = null): void
    {
        $this->chargerOptions();
        $this->reset(['pubTitre', 'pubResponsable']);
        $this->resetValidation();
        $this->pubDate = $date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : now()->toDateString();
        $this->pubType = 'autre';
        $this->pubCanaux = $this->options['plan']['autre'] ?? [];
        $this->modale = true;
    }

    public function fermerPlanification(): void
    {
        $this->modale = false;
    }

    /** Canaux pré-cochés selon le plan de diffusion de la Com (modifiables). */
    public function updatedPubType(string $type): void
    {
        $plan = $this->options['plan'][$type] ?? [];
        if ($plan !== []) {
            $this->pubCanaux = $plan;
        }
    }

    public function planifierPublication(): void
    {
        $this->validate([
            'pubDate' => 'required|date',
            'pubTitre' => 'required|string|min:3|max:200',
        ], [
            'pubDate.required' => 'Choisissez la date de publication.',
            'pubTitre.required' => 'Indiquez le sujet de la publication.',
            'pubTitre.min' => 'Indiquez le sujet de la publication.',
        ]);
        $r = Api::post('/admin/communication/publications', [
            'date' => $this->pubDate,
            'titre' => trim($this->pubTitre),
            'type' => $this->pubType,
            'canaux' => array_values($this->pubCanaux),
            // Vide : l'API prend le responsable du tour de rôle à cette date.
            'responsable_id' => ctype_digit($this->pubResponsable) ? (int) $this->pubResponsable : null,
        ], Api::token());

        $titre = trim($this->pubTitre);
        if ($this->toast($r, 'Publication planifiée. Créer son visuel ?', ['libelle' => 'Créer le visuel', 'url' => route('admin.visuels', ['titre' => $titre])])) {
            $this->modale = false;
            $this->dispatch('publication-planifiee'); // le bloc calendrier se met à jour
        } else {
            $this->addError('pubTitre', collect($r['errors'] ?? [])->flatten()->first() ?? ($r['message'] ?? 'Enregistrement impossible.'));
        }
    }

    /** « Marquer publié » depuis la carte « Votre priorité du jour ». */
    public function marquerPublie(int $id, ?string $lien = null): void
    {
        $lien = trim((string) $lien);
        $r = Api::post("/admin/communication/publications/{$id}/publie", $lien !== '' ? ['lien' => $lien] : [], Api::token());
        if ($this->toast($r, 'Bravo, publication marquée « publié ».')) {
            $this->dispatch('publication-planifiee'); // même rafraîchissement du bloc calendrier
        }
    }

    // ── Demandes ────────────────────────────────────────────────────────

    public function accepter(int $id): void
    {
        $r = Api::put("/admin/demandes-communication/{$id}/statut", ['statut' => 'acceptee'], Api::token());
        $this->toast($r, 'Demande acceptée : le demandeur est prévenu par e-mail. Planifiez-la quand vous voulez.',
            ['libelle' => 'Voir la demande', 'url' => route('admin.demandes-communication', ['fiche' => $id])]);
        $this->fermerDemande();
    }

    /** Ouvre la saisie « Planifier » (date + responsable) ou « Refuser » (motif) d'une demande. */
    public function choisirDemande(int $id, string $action, ?string $dateSouhaitee = null): void
    {
        if ($this->demandeId === $id && $this->demandeAction === $action) {
            $this->fermerDemande();

            return;
        }
        $this->resetValidation();
        $this->demandeId = $id;
        $this->demandeAction = in_array($action, ['planifier', 'refuser'], true) ? $action : '';
        $this->motif = '';
        $this->demResponsable = '';
        // Date proposée : celle souhaitée par le demandeur, jamais dans le passé.
        $this->demDate = $dateSouhaitee && $dateSouhaitee >= now()->toDateString() ? $dateSouhaitee : now()->toDateString();
        if ($this->demandeAction === 'planifier') {
            $this->chargerOptions();
        }
    }

    public function fermerDemande(): void
    {
        $this->reset(['demandeId', 'demandeAction', 'demDate', 'demResponsable', 'motif']);
        $this->resetValidation();
    }

    public function planifierDemande(): void
    {
        $this->validate(['demDate' => 'required|date'], ['demDate.required' => 'Choisissez la date de publication.']);
        $r = Api::post("/admin/demandes-communication/{$this->demandeId}/planifier", [
            'date' => $this->demDate,
            'responsable_id' => ctype_digit($this->demResponsable) ? (int) $this->demResponsable : null,
        ], Api::token());
        $pub = $r['publication_id'] ?? null;
        if ($this->toast($r, 'Demande planifiée : le demandeur est prévenu par e-mail.',
            ['libelle' => 'Voir dans le calendrier', 'url' => route('admin.calendrier-editorial', array_filter(['publication' => $pub, 'mois' => substr($this->demDate, 0, 7)]))])) {
            $this->fermerDemande();
            $this->dispatch('publication-planifiee');
        }
    }

    public function refuserDemande(): void
    {
        $this->validate(['motif' => 'required|string|min:3|max:2000'], [
            'motif.required' => 'Indiquez le motif du refus : il sera transmis au demandeur.',
            'motif.min' => 'Indiquez le motif du refus : il sera transmis au demandeur.',
        ]);
        $r = Api::put("/admin/demandes-communication/{$this->demandeId}/statut", ['statut' => 'refusee', 'motif' => trim($this->motif)], Api::token());
        if ($this->toast($r, 'Demande refusée : le motif est transmis au demandeur.')) {
            $this->fermerDemande();
        }
    }

    // ── Données ─────────────────────────────────────────────────────────

    /**
     * La prochaine action la plus importante : publications en retard, puis
     * publications du jour non publiées, demandes urgentes, réponses
     * CathoImpact à examiner, demandes nouvelles. Sinon « Tout est à jour »,
     * avec une suggestion (planifier la semaine prochaine si elle est vide).
     */
    private function priorite(array $cockpit, ?array $jour): array
    {
        $enAttente = fn ($p) => in_array($p['statut'], ['prevu', 'pret', 'non_publie'], true);
        $demandes = collect($cockpit['a_traiter']['demandes'] ?? []);

        if ($jour && ($jour['retards'] ?? []) !== []) {
            return ['type' => 'retard', 'publication' => $jour['retards'][0], 'nombre' => (int) $jour['chiffres']['retards']];
        }
        $duJour = collect($jour['aujourdhui'] ?? [])->filter($enAttente)->values();
        if ($duJour->isNotEmpty()) {
            return ['type' => 'jour', 'publication' => $duJour->first(), 'nombre' => $duJour->count()];
        }
        if ($d = $demandes->firstWhere('urgente', true)) {
            return ['type' => 'demande_urgente', 'demande' => $d, 'nombre' => $demandes->where('urgente', true)->count()];
        }
        if (($cockpit['a_traiter']['cathoimpact_total'] ?? 0) > 0) {
            return ['type' => 'cathoimpact', 'reponse' => $cockpit['a_traiter']['cathoimpact'][0], 'nombre' => (int) $cockpit['a_traiter']['cathoimpact_total']];
        }
        if ($d = $demandes->firstWhere('statut', 'nouvelle')) {
            return ['type' => 'demande', 'demande' => $d, 'nombre' => $demandes->where('statut', 'nouvelle')->count()];
        }

        return ['type' => 'ok', 'suggestion' => ($cockpit['chiffres']['publications_semaine_prochaine'] ?? 1) === 0 ? 'planifier' : 'visuel'];
    }

    public function render()
    {
        $cockpit = Api::get('/admin/communication/cockpit', [], Api::token());
        $cockpit = ($cockpit['ok'] ?? false) ? $cockpit : null;
        $jour = Api::get('/admin/communication/calendrier/aujourdhui', [], Api::token());
        $jour = ($jour['ok'] ?? false) ? $jour : null;

        // Visiteurs du site : fichiers de mesure partagés, gardés 60 s en cache.
        $visites = rescue(fn () => Cache::remember('cockpit-com-visites-'.now()->format('Y-m'), 60,
            fn () => collect(StatistiquesSite::mois(now()->year, now()->month))->only(['visiteurs', 'pages_vues', 'evolution', 'jours_mesures'])->all()), null, false);

        return view('livewire.departements.partials.cockpit-communication', [
            'cockpit' => $cockpit,
            'jour' => $jour,
            'visites' => $visites,
            'priorite' => $cockpit ? $this->priorite($cockpit, $jour) : null,
            'pastilles' => AdminNav::pastilles(),
        ]);
    }
}
