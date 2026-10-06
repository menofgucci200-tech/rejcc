<?php

namespace App\Livewire\Member;

use App\Livewire\Concerns\HandlesMedia;
use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;
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

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    #[Url(except: '')]
    public string $groupe = '';

    #[Url(as: 'ville', except: '')]
    public string $villeFiltre = '';

    #[Url(as: 'mode', except: '')]
    public string $modeFiltre = '';

    #[Url(except: 'recents')]
    public string $tri = 'recents';

    #[Url(except: false)]
    public bool $favoris = false;

    public bool $alertesOuvertes = false;

    public ?array $fiche = null;

    /** Ouvre directement les candidatures de l'offre (lien de notification). */
    #[Url(as: 'candidatures', except: false)]
    public bool $voirCandidatures = false;

    public array $candidatures = [];

    public array $notes = [];

    public string $motCandidat = '';

    // ── Postuler ─────────────────────────────────────────────────────────
    public bool $postulerOuvert = false;

    public string $messageCandidature = '';

    /** CV joint (fichier temporaire). */
    public $cvFile = null;

    public string $cvUrl = '';

    public string $cvName = '';

    public ?string $infoFiche = null;

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

    public function effacerFiltres(): void
    {
        $this->reset(['recherche', 'groupe', 'villeFiltre', 'modeFiltre', 'favoris']);
        $this->filtre = 'tous';
        $this->tri = 'recents';
    }

    public function basculerFavori(int $id): void
    {
        $r = Api::post("/opportunities/{$id}/favori", [], Api::token());
        if ($this->fiche && $this->fiche['id'] === $id) {
            $this->fiche['favori'] = (bool) ($r['favori'] ?? false);
        }
    }

    /** « M'alerter » : crée une alerte à partir des filtres en cours. */
    public function creerAlerte(): void
    {
        $r = Api::post('/job-alerts', array_filter([
            'type' => $this->filtre !== 'tous' ? $this->filtre : null,
            'group_id' => $this->groupe ? (int) $this->groupe : null,
            'ville' => trim($this->villeFiltre) ?: null,
            'q' => trim($this->recherche) ?: null,
        ]), Api::token());
        $this->alertesOuvertes = true;
        $this->message = ($r['ok'] ?? false) ? 'Alerte créée : vous serez notifié(e) de chaque nouvelle offre « '.($r['alerte']['libelle'] ?? '').' ».' : null;
        $this->erreur = ($r['ok'] ?? false) ? null : ($r['message'] ?? 'Une erreur est survenue.');
    }

    public function supprimerAlerte(int $id): void
    {
        Api::delete("/job-alerts/{$id}", Api::token());
        $this->message = 'Alerte supprimée.';
    }

    public function setOnglet(string $o): void
    {
        $this->onglet = in_array($o, ['offres', 'mes', 'candidatures'], true) ? $o : 'offres';
    }

    // ── Fiche ────────────────────────────────────────────────────────────

    public function voir(int $id, bool $garder = false): void
    {
        if (! $garder) {
            $this->infoFiche = null;
            $this->postulerOuvert = false;
            $this->motCandidat = '';
        }
        $r = Api::get("/opportunities/{$id}", [], Api::token());
        if ($r['ok'] ?? false) {
            $this->fiche = $r['opportunity'];
            $this->focus = $id;
            if ($this->voirCandidatures && ($this->fiche['mine'] ?? false)) {
                $this->chargerCandidatures();
            } else {
                $this->voirCandidatures = false;
            }
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
        $this->voirCandidatures = false;
        $this->candidatures = [];
    }

    // ── Postuler ─────────────────────────────────────────────────────────

    public function ouvrirPostuler(): void
    {
        $this->postulerOuvert = ! $this->postulerOuvert;
        $this->reset(['messageCandidature', 'cvFile', 'cvUrl', 'cvName']);
        $this->resetValidation();
    }

    public function updatedCvFile(): void
    {
        $this->validate(['cvFile' => 'file|max:10240|mimes:pdf,doc,docx'], [
            'cvFile.max' => 'Le CV ne doit pas dépasser 10 Mo.',
            'cvFile.mimes' => 'Le CV doit être un fichier PDF ou Word.',
        ]);
        $chemin = $this->cvFile->store('cv/'.date('Y/m'), 'uploads');
        $this->cvUrl = Storage::disk('uploads')->url($chemin);
        $this->cvName = $this->cvFile->getClientOriginalName();
        $this->cvFile = null;
    }

    public function retirerCv(): void
    {
        $this->cvUrl = $this->cvName = '';
    }

    public function postuler(): void
    {
        $r = Api::post("/opportunities/{$this->fiche['id']}/postuler", array_filter([
            'message' => trim($this->messageCandidature), 'cv_url' => $this->cvUrl ?: null, 'cv_name' => $this->cvName ?: null,
        ]), Api::token());
        if ($r['ok'] ?? false) {
            $this->postulerOuvert = false;
        }
        $this->voir($this->fiche['id'], true);
        $this->infoFiche = ($r['ok'] ?? false)
            ? 'Candidature envoyée ! Le recruteur est prévenu ; suivez-la dans « Mes candidatures ».'
            : ($r['message'] ?? 'Une erreur est survenue.');
    }

    public function retirerCandidature(): void
    {
        $r = Api::delete("/opportunities/{$this->fiche['id']}/candidature", Api::token());
        $this->voir($this->fiche['id'], true);
        $this->infoFiche = ($r['ok'] ?? false) ? 'Candidature retirée.' : ($r['message'] ?? 'Une erreur est survenue.');
    }

    // ── Candidatures reçues (auteur) ─────────────────────────────────────

    public function chargerCandidatures(): void
    {
        $this->candidatures = Api::get("/opportunities/{$this->fiche['id']}/candidatures", [], Api::token())['candidatures'] ?? [];
        $this->notes = collect($this->candidatures)->mapWithKeys(fn ($c) => [$c['id'] => (string) ($c['note'] ?? '')])->all();
        $this->voirCandidatures = true;
    }

    public function basculerCandidatures(): void
    {
        if ($this->voirCandidatures) {
            $this->voirCandidatures = false;

            return;
        }
        $this->chargerCandidatures();
        $this->voir($this->fiche['id'], true);
    }

    public function statutCandidature(int $cid, string $statut): void
    {
        $r = Api::post("/opportunities/{$this->fiche['id']}/candidatures/{$cid}/statut", array_filter([
            'statut' => $statut, 'message' => trim($this->motCandidat) ?: null,
        ]), Api::token());
        $this->motCandidat = '';
        $this->chargerCandidatures();
        $this->infoFiche = ($r['ok'] ?? false)
            ? ['preselection' => 'Candidature présélectionnée : le candidat est prévenu.', 'retenue' => 'Candidature retenue : le candidat est prévenu.', 'non_retenue' => 'Candidature non retenue : le candidat est prévenu avec bienveillance.'][$statut] ?? 'Candidature mise à jour.'
            : ($r['message'] ?? 'Une erreur est survenue.');
    }

    public function enregistrerNote(int $cid): void
    {
        $r = Api::post("/opportunities/{$this->fiche['id']}/candidatures/{$cid}/statut", ['note' => trim($this->notes[$cid] ?? '')], Api::token());
        $this->infoFiche = ($r['ok'] ?? false) ? 'Note enregistrée (visible de vous seul).' : ($r['message'] ?? 'Une erreur est survenue.');
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
        $data = Api::get('/opportunities', array_filter([
            'q' => trim($this->recherche), 'type' => $this->filtre !== 'tous' ? $this->filtre : null, 'groupe' => $this->groupe,
            'ville' => $this->villeFiltre, 'teletravail' => $this->modeFiltre, 'tri' => $this->tri !== 'recents' ? $this->tri : null,
            'favoris' => $this->favoris ? 1 : null,
        ]), Api::token());
        $offres = Collection::make($data['opportunities'] ?? []);

        $mesCandidatures = $this->onglet === 'candidatures'
            ? Collection::make(Api::get('/mes-candidatures', [], Api::token())['candidatures'] ?? [])
            : collect();

        return view('livewire.member.emplois', [
            'offres' => $offres,
            'mesCandidatures' => $mesCandidatures,
            'mesOffres' => Collection::make($data['mes_offres'] ?? []),
            'peutPublier' => (bool) ($data['peut_publier'] ?? false),
            'villes' => $data['villes'] ?? [],
            'alertes' => $data['alertes'] ?? [],
            'nbFavoris' => (int) ($data['nb_favoris'] ?? 0),
            'filtresActifs' => trim($this->recherche) !== '' || $this->filtre !== 'tous' || $this->groupe !== '' || $this->villeFiltre !== '' || $this->modeFiltre !== '' || $this->favoris,
            'categories' => $data['categories'] ?? [],
            'types' => $data['types'] ?? [],
            'contrats' => $data['contrats'] ?? [],
            'modesTravail' => $data['teletravail'] ?? [],
        ]);
    }
}
