<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\AdminNav;
use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Emploi & Stage : validation des offres proposées par les membres
 * (publier, demander une correction, refuser, retirer — motif transmis),
 * correction, publication directe par l'équipe.
 */
#[Layout('layouts.admin-light')]
class Emplois extends Component
{
    use HandlesMedia;

    #[Url(except: 'en_attente')]
    public string $filtre = 'en_attente';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    public ?int $ouvert = null;

    public ?string $message = null;

    public ?string $erreur = null;

    public ?string $decision = null;

    public string $motif = '';

    // Formulaire (création par l'équipe ou correction)
    public bool $showForm = false;

    public ?int $editingId = null;

    public array $f = [];

    public string $note = '';

    public const FILTRES = ['en_attente' => 'À valider', 'signalee' => 'Signalées', 'a_corriger' => 'À corriger', 'publiee' => 'En ligne', 'expiree' => 'Expirées', 'pourvue' => 'Pourvues', 'cloturee' => 'Clôturées', 'refusee' => 'Refusées / retirées', '' => 'Toutes'];

    public const MOTIFS = [
        'corriger' => [
            "Précisez l'entreprise qui recrute et la ville du poste.",
            'Détaillez les missions et le profil recherché.',
            'Indiquez la rémunération ou la gratification (obligatoire pour un stage).',
        ],
        'refuser' => [
            "L'offre ne correspond pas à un emploi, un stage ou une mission (voir la Marketplace).",
            "L'offre est en double avec une offre déjà publiée.",
            'Les informations ne permettent pas de vérifier le sérieux de l\'offre.',
        ],
        'retirer' => [
            'Offre signalée comme frauduleuse par des membres.',
            'Le poste est déjà pourvu.',
        ],
    ];

    private function vide(): array
    {
        return ['title' => '', 'type' => 'emploi', 'contrat' => 'cdi', 'entreprise' => '', 'group_id' => '', 'lieu' => '', 'teletravail' => 'sur_site',
            'remuneration' => '', 'debut' => '', 'duree' => '', 'description' => '', 'missions' => '', 'profil' => '', 'competences' => '',
            'site_url' => '', 'contact' => '', 'deadline' => ''];
    }

    public function setFiltre(string $f): void
    {
        $this->filtre = array_key_exists($f, self::FILTRES) ? $f : 'en_attente';
        $this->ouvert = null;
        $this->decision = null;
    }

    public function basculer(int $id): void
    {
        $this->ouvert = $this->ouvert === $id ? null : $id;
        $this->decision = null;
        $this->message = $this->erreur = null;
    }

    public function preparer(int $id, string $decision): void
    {
        $this->ouvert = $id;
        $this->decision = $decision;
        $this->motif = '';
        $this->erreur = null;
    }

    public function annulerDecision(): void
    {
        $this->decision = null;
    }

    public function confirmer(): void
    {
        $r = Api::post("/admin/opportunities/{$this->ouvert}/decision", array_filter(['decision' => $this->decision, 'motif' => trim($this->motif) ?: null]), Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Une erreur est survenue.';

            return;
        }
        $this->message = match ($this->decision) {
            'publier' => "Offre publiée : elle est en ligne et l'auteur est prévenu.",
            'corriger' => "Demande de correction envoyée à l'auteur.",
            'retirer' => "Offre retirée : l'auteur est prévenu du motif.",
            default => "Offre refusée : le motif a été transmis à l'auteur.",
        };
        $this->decision = null;
        $this->ouvert = null;
        $this->erreur = null;
        AdminNav::oublier();
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->f = $this->vide();
        $this->note = '';
        $this->clearMedia();
        $this->erreur = $this->message = null;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $o = collect(Api::get('/admin/opportunities', [], Api::token())['opportunities'] ?? [])->firstWhere('id', $id);
        if (! $o) {
            return;
        }
        $this->editingId = $id;
        $this->f = array_merge($this->vide(), array_map(fn ($v) => $v ?? '', array_intersect_key($o, $this->vide())), [
            'group_id' => (string) ($o['groupe']['id'] ?? ''),
            'contrat' => $o['contrat'] ?? 'cdi',
            'competences' => implode(', ', $o['competences'] ?? []),
        ]);
        $this->note = '';
        $this->fillMedia($o['media_url'] ?? null);
        $this->erreur = $this->message = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        $d = $this->f;
        $data = [
            'title' => trim($d['title']), 'type' => $d['type'], 'contrat' => $d['type'] === 'emploi' ? $d['contrat'] : null,
            'entreprise' => trim($d['entreprise']), 'group_id' => (int) $d['group_id'], 'lieu' => trim($d['lieu']),
            'teletravail' => $d['teletravail'], 'remuneration' => trim($d['remuneration']) ?: null, 'debut' => $d['debut'] ?: null,
            'duree' => trim($d['duree']) ?: null, 'description' => trim($d['description']), 'missions' => trim($d['missions']) ?: null,
            'profil' => trim($d['profil']) ?: null, 'competences' => array_values(array_filter(array_map('trim', explode(',', $d['competences'])))),
            'site_url' => trim($d['site_url']) ?: null, 'contact' => trim($d['contact']) ?: null, 'deadline' => $d['deadline'] ?: null,
            'media_url' => $this->mediaUrl ?: null, 'media_name' => $this->mediaName ?: null, 'note' => trim($this->note),
        ];
        $r = $this->editingId
            ? Api::put("/admin/opportunities/{$this->editingId}", $data, Api::token())
            : Api::post('/admin/opportunities', $data, Api::token());

        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? "L'enregistrement a échoué.";

            return;
        }
        $this->message = $this->editingId ? "Offre corrigée : l'auteur est prévenu." : 'Offre publiée directement.';
        $this->erreur = null;
        $this->closeForm();
        if (! $this->editingId) {
            $this->filtre = 'publiee';
        }
    }

    public function classerSignalements(int $id): void
    {
        Api::post("/admin/opportunities/{$id}/signalements", [], Api::token());
        $this->message = 'Signalements classés sans suite.';
        AdminNav::oublier();
    }

    public function delete(int $id): void
    {
        Api::delete("/admin/opportunities/{$id}", Api::token());
        $this->ouvert = null;
        $this->message = 'Offre supprimée.';
        AdminNav::oublier();
    }

    public function render()
    {
        $data = Api::get('/admin/opportunities', array_filter(['statut' => $this->filtre, 'q' => trim($this->recherche)]), Api::token());
        $compteurs = $data['compteurs'] ?? [];
        $compteurs[''] = array_sum($compteurs);
        $compteurs['signalee'] = (int) ($data['signalees'] ?? 0);

        return view('livewire.admin.emplois', [
            'offres' => Collection::make($data['opportunities'] ?? []),
            'stats' => $data['stats'] ?? [],
            'compteurs' => $compteurs,
            'categories' => $data['categories'] ?? [],
            'types' => $data['types'] ?? [],
            'contrats' => $data['contrats'] ?? [],
            'modesTravail' => $data['teletravail'] ?? [],
        ]);
    }
}
