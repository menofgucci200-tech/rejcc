<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Évaluation des projets proposés par les membres : valider (avec le stade),
 * demander des précisions ou refuser — le motif est transmis au porteur —,
 * corriger une fiche, supprimer.
 */
#[Layout('layouts.admin-light')]
class Projets extends Component
{
    #[Url(except: 'evaluation')]
    public string $filtre = 'evaluation';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    public ?int $ouvert = null;

    public ?string $message = null;

    public ?string $erreur = null;

    // Décision
    public ?string $decision = null;

    public string $motif = '';

    public string $stadeDecision = '';

    // Correction d'une fiche
    public ?int $corrigerId = null;

    public array $correction = [];

    public string $noteCorrection = '';

    public const FILTRES = ['evaluation' => 'À évaluer', 'a_completer' => 'À compléter', 'valide' => 'Validés', 'refuse' => 'Refusés', 'retire' => 'Retirés', '' => 'Tous'];

    public const MOTIFS = [
        'completer' => [
            'Précisez le public visé et la façon dont vous allez l\'atteindre.',
            'Détaillez où en est le projet aujourd\'hui (étapes réalisées, prochaines étapes).',
            'Ajoutez un visuel ou un lien qui présente le projet.',
        ],
        'refuser' => [
            'Le projet ne correspond pas aux valeurs ou au cadre du réseau.',
            'Le projet est en double avec un projet déjà présenté.',
            'La présentation ne permet pas de comprendre le projet.',
        ],
    ];

    public function setFiltre(string $f): void
    {
        $this->filtre = array_key_exists($f, self::FILTRES) ? $f : 'evaluation';
        $this->ouvert = null;
        $this->decision = null;
    }

    public function basculer(int $id): void
    {
        $this->ouvert = $this->ouvert === $id ? null : $id;
        $this->decision = null;
        $this->message = $this->erreur = null;
    }

    public function preparer(int $id, string $decision, string $stade = ''): void
    {
        $this->ouvert = $id;
        $this->decision = $decision;
        $this->motif = '';
        $this->stadeDecision = $stade;
        $this->erreur = null;
    }

    public function annulerDecision(): void
    {
        $this->decision = null;
    }

    public function confirmer(): void
    {
        $r = Api::post("/admin/projects/{$this->ouvert}/decision", array_filter([
            'decision' => $this->decision,
            'motif' => trim($this->motif) ?: null,
            'stade' => $this->stadeDecision ?: null,
        ]), Api::token());

        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Une erreur est survenue.';

            return;
        }
        $this->message = match ($this->decision) {
            'valider' => 'Projet validé : il est visible des membres et le porteur est prévenu.',
            'completer' => 'Demande de précisions envoyée au porteur.',
            default => 'Projet refusé : le motif a été transmis au porteur.',
        };
        $this->decision = null;
        $this->ouvert = null;
        $this->erreur = null;
        AdminNav::oublier();
    }

    public function basculerUne(int $id): void
    {
        $r = Api::post("/admin/projects/{$id}/une", [], Api::token());
        $this->message = ($r['ok'] ?? false)
            ? (($r['a_la_une'] ?? false) ? 'Projet mis à la une : il apparaît en tête de liste, et le porteur est prévenu.' : "Projet retiré de la une.")
            : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Une erreur est survenue.');
    }

    public function ouvrirCorrection(int $id): void
    {
        $p = collect(Api::get('/admin/projects', [], Api::token())['projects'] ?? [])->firstWhere('id', $id);
        if (! $p) {
            return;
        }
        $this->corrigerId = $id;
        $this->decision = null;
        $this->noteCorrection = '';
        $this->correction = [
            'title' => $p['title'], 'accroche' => (string) $p['accroche'], 'description' => $p['description'],
            'group_id' => (string) ($p['groupe']['id'] ?? ''), 'stade' => $p['stade'], 'ville' => (string) $p['ville'],
            // Champs conservés tels quels.
            'probleme' => $p['probleme'], 'solution' => $p['solution'], 'cible' => $p['cible'], 'impact' => $p['impact'],
            'besoins' => $p['besoins'], 'lien' => $p['lien'], 'image' => $p['image'], 'members_count' => $p['members_count'], 'public_ok' => $p['public_ok'],
        ];
    }

    public function fermerCorrection(): void
    {
        $this->corrigerId = null;
    }

    public function enregistrerCorrection(): void
    {
        $data = $this->correction;
        $data['group_id'] = (int) $data['group_id'];
        foreach (['accroche', 'ville'] as $k) {
            $data[$k] = trim((string) $data[$k]) ?: null;
        }
        $r = Api::put("/admin/projects/{$this->corrigerId}", $data + ['note' => trim($this->noteCorrection)], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Une erreur est survenue.';

            return;
        }
        $this->corrigerId = null;
        $this->erreur = null;
        $this->message = 'Fiche corrigée : le porteur est prévenu.';
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/projects/{$id}", Api::token());
        $this->ouvert = null;
        $this->message = 'Projet supprimé.';
        AdminNav::oublier();
    }

    public function render()
    {
        $data = Api::get('/admin/projects', array_filter(['statut' => $this->filtre, 'q' => trim($this->recherche)]), Api::token());
        $compteurs = $data['compteurs'] ?? [];
        $compteurs[''] = array_sum($compteurs);

        return view('livewire.admin.projets', [
            'projets' => Collection::make($data['projects'] ?? []),
            'compteurs' => $compteurs,
            'stades' => $data['stades'] ?? [],
            'categories' => $data['categories'] ?? [],
            'besoins' => $data['besoins'] ?? [],
        ]);
    }
}
