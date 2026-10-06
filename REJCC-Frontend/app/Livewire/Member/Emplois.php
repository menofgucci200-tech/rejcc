<?php

namespace App\Livewire\Member;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Emploi & Stage : offres en ligne du réseau, fiche détaillée, et « Mes
 * offres » (proposées par le membre, validées par l'équipe avant
 * publication : modifier, marquer pourvue, clôturer, prolonger).
 */
#[Layout('layouts.member-light')]
class Emplois extends Component
{
    use HandlesMedia;

    /** Offre ouverte (lien partageable, notification). */
    #[Url(as: 'offre', except: null)]
    public ?int $focus = null;

    #[Url(except: 'offres')]
    public string $onglet = 'offres';

    #[Url(as: 'type', except: 'tous')]
    public string $filtre = 'tous';

    public ?array $fiche = null;

    public ?string $message = null;

    public ?string $erreur = null;

    // ── Formulaire ───────────────────────────────────────────────────────
    public bool $showForm = false;

    public ?int $editingId = null;

    public ?string $statutEdition = null;

    public string $title = '';

    public string $type = 'emploi';

    public string $contrat = 'cdi';

    public string $entreprise = '';

    public string $groupId = '';

    public string $site_url = '';

    public string $lieu = '';

    public string $teletravail = 'sur_site';

    public string $remuneration = '';

    public string $debut = '';

    public string $duree = '';

    public string $description = '';

    public string $missions = '';

    public string $profil = '';

    public string $competences = '';

    public string $contact = '';

    public string $deadline = '';

    public function mount(): void
    {
        if ($this->focus) {
            $this->voir($this->focus);
        }
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:4|max:160',
            'type' => 'required|in:emploi,stage,alternance,freelance,mission',
            'entreprise' => 'required|string|min:2|max:160',
            'groupId' => 'required',
            'lieu' => 'required|string|min:2|max:160',
            'site_url' => 'nullable|url|max:500',
            'description' => 'required|string|min:20|max:3000',
            'missions' => 'nullable|string|max:3000',
            'profil' => 'nullable|string|max:2000',
            'remuneration' => 'nullable|string|max:120',
            'duree' => 'nullable|string|max:60',
            'debut' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:today',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => "Donnez un intitulé à l'offre.",
            'title.min' => "L'intitulé est trop court.",
            'entreprise.required' => "Indiquez l'entreprise ou la structure qui recrute.",
            'groupId.required' => "Choisissez le secteur de l'offre.",
            'lieu.required' => 'Indiquez la ville du poste.',
            'description.required' => 'Présentez le poste ou le stage.',
            'description.min' => "Présentez l'offre en quelques phrases (20 caractères minimum).",
            'site_url.url' => 'Le lien du site doit être une adresse complète (https://…).',
            'deadline.after_or_equal' => "La date limite doit être aujourd'hui ou plus tard.",
        ];
    }

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    public function setOnglet(string $o): void
    {
        $this->onglet = in_array($o, ['offres', 'mes'], true) ? $o : 'offres';
    }

    // ── Fiche ────────────────────────────────────────────────────────────

    public function voir(int $id): void
    {
        $r = Api::get("/opportunities/{$id}", [], Api::token());
        if ($r['ok'] ?? false) {
            $this->fiche = $r['opportunity'];
            $this->focus = $id;
        } else {
            $this->fiche = null;
            $this->focus = null;
            $this->erreur = $r['message'] ?? 'Offre introuvable.';
        }
    }

    public function fermerFiche(): void
    {
        $this->fiche = null;
        $this->focus = null;
    }

    // ── Mes offres ───────────────────────────────────────────────────────

    private function retour(array $r, string $succes): void
    {
        $this->message = ($r['ok'] ?? false) ? $succes : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Une erreur est survenue.');
        if ($this->fiche) {
            $this->voir($this->fiche['id']);
        }
    }

    public function changerStatut(int $id, string $statut): void
    {
        $r = Api::post("/opportunities/{$id}/statut", ['statut' => $statut], Api::token());
        $this->retour($r, match ($statut) {
            'pourvue' => 'Bravo ! L\'offre est marquée pourvue : elle n\'est plus visible des membres.',
            'cloturee' => "Offre clôturée : elle n'est plus visible des membres.",
            default => 'Offre remise en ligne pour 60 jours.',
        });
    }

    public function prolonger(int $id): void
    {
        $r = Api::post("/opportunities/{$id}/prolonger", [], Api::token());
        $this->retour($r, 'Offre prolongée de 30 jours'.(($r['expire_le'] ?? null) ? ' (jusqu\'au '.\Carbon\Carbon::parse($r['expire_le'])->locale('fr')->isoFormat('D MMMM').')' : '').'.');
    }

    public function supprimer(int $id): void
    {
        Api::delete("/opportunities/{$id}", Api::token());
        $this->fermerFiche();
        $this->message = 'Offre supprimée.';
    }

    // ── Formulaire ───────────────────────────────────────────────────────

    public function openForm(): void
    {
        $this->reset(['editingId', 'statutEdition', 'title', 'entreprise', 'groupId', 'site_url', 'lieu', 'remuneration', 'debut', 'duree',
            'description', 'missions', 'profil', 'competences', 'contact', 'deadline']);
        $this->type = 'emploi';
        $this->contrat = 'cdi';
        $this->teletravail = 'sur_site';
        $this->clearMedia();
        $this->resetValidation();
        $this->message = $this->erreur = null;
        $this->fermerFiche();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $o = Api::get("/opportunities/{$id}", [], Api::token())['opportunity'] ?? null;
        if (! $o || ! ($o['mine'] ?? false)) {
            return;
        }
        $this->editingId = $id;
        $this->statutEdition = $o['statut'];
        $this->title = $o['title'];
        $this->type = $o['type'];
        $this->contrat = $o['contrat'] ?? 'cdi';
        $this->entreprise = (string) $o['entreprise'];
        $this->groupId = (string) ($o['groupe']['id'] ?? '');
        $this->site_url = (string) $o['site_url'];
        $this->lieu = (string) $o['lieu'];
        $this->teletravail = $o['teletravail'] ?? 'sur_site';
        $this->remuneration = (string) $o['remuneration'];
        $this->debut = (string) $o['debut'];
        $this->duree = (string) $o['duree'];
        $this->description = $o['description'];
        $this->missions = (string) $o['missions'];
        $this->profil = (string) $o['profil'];
        $this->competences = implode(', ', $o['competences'] ?? []);
        $this->contact = (string) ($o['contact'] ?? '');
        $this->deadline = (string) $o['deadline'];
        $this->fillMedia($o['media_url'] ?? null);
        $this->resetValidation();
        $this->message = $this->erreur = null;
        $this->fermerFiche();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public function publier(): void
    {
        $this->validate();

        $data = [
            'title' => trim($this->title),
            'type' => $this->type,
            'contrat' => $this->type === 'emploi' ? $this->contrat : null,
            'entreprise' => trim($this->entreprise),
            'group_id' => (int) $this->groupId,
            'site_url' => trim($this->site_url) ?: null,
            'lieu' => trim($this->lieu),
            'teletravail' => $this->teletravail,
            'remuneration' => trim($this->remuneration) ?: null,
            'debut' => $this->debut ?: null,
            'duree' => trim($this->duree) ?: null,
            'description' => trim($this->description),
            'missions' => trim($this->missions) ?: null,
            'profil' => trim($this->profil) ?: null,
            'competences' => array_values(array_filter(array_map('trim', explode(',', $this->competences)))),
            'contact' => trim($this->contact) ?: null,
            'deadline' => $this->deadline ?: null,
            'media_url' => $this->mediaUrl ?: null,
            'media_name' => $this->mediaName ?: null,
        ];
        $r = $this->editingId
            ? Api::put("/opportunities/{$this->editingId}", $data, Api::token())
            : Api::post('/opportunities', $data, Api::token());

        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'La publication a échoué.';

            return;
        }

        $this->message = match (true) {
            ! $this->editingId => "Offre envoyée ! L'équipe REJCC la vérifie avant publication : vous serez notifié(e).",
            (bool) ($r['resoumise'] ?? false) => "Offre corrigée et renvoyée en validation : l'équipe est prévenue.",
            default => 'Modifications enregistrées.',
        };
        $this->erreur = null;
        $this->closeForm();
        $this->onglet = 'mes';
    }

    public function render()
    {
        $data = Api::get('/opportunities', [], Api::token());
        $offres = Collection::make($data['opportunities'] ?? [])
            ->when($this->filtre !== 'tous', fn ($c) => $c->where('type', $this->filtre))->values();

        return view('livewire.member.emplois', [
            'offres' => $offres,
            'mesOffres' => Collection::make($data['mes_offres'] ?? []),
            'peutPublier' => (bool) ($data['peut_publier'] ?? false),
            'categories' => $data['categories'] ?? [],
            'types' => $data['types'] ?? [],
            'contrats' => $data['contrats'] ?? [],
            'modesTravail' => $data['teletravail'] ?? [],
        ]);
    }
}
