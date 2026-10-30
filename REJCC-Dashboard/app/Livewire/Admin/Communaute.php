<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Administration de la Communauté REJCC : salons (création, réglages,
 * archivage, ordre), référents, messages signalés, sanctions, mots interdits
 * et journal de modération.
 */
#[Layout('layouts.admin-light')]
class Communaute extends Component
{
    #[Url(as: 'onglet')]
    public string $onglet = 'salons';

    public array $donnees = [];

    public bool $ok = true;

    public ?string $message = null;

    public ?string $erreur = null;

    /** Formulaire de salon (création ou modification). */
    public ?array $formulaire = null;

    /** Salon dont on gère les référents. */
    public ?int $referentsSalonId = null;

    public string $rechercheMembre = '';

    public array $resultats = [];

    /** Fil d'un salon consulté par la modération. */
    public ?array $fil = null;

    public string $motsInterdits = '';

    /** Texte d'une annonce à publier dans le canal Annonces. */
    public string $annonce = '';


    public function mount(): void
    {
        $this->charger();
    }

    public function charger(): void
    {
        $r = Api::get('/admin/communaute', [], Api::token());
        $this->ok = (bool) ($r['ok'] ?? false);
        $this->donnees = $this->ok ? $r : [];
        $this->motsInterdits = (string) ($r['mots_interdits'] ?? '');
    }

    private function resultat(array $r, string $succes): void
    {
        if ($r['ok'] ?? false) {
            $this->message = is_string($r['message'] ?? null) ? $r['message'] : $succes;
            $this->erreur = null;
            AdminNav::oublier();
            $this->charger();
        } else {
            $this->erreur = is_string($r['message'] ?? null) ? $r['message'] : 'Une erreur est survenue.';
            $this->message = null;
        }
    }

    // ── Salons ───────────────────────────────────────────────────────────

    public function nouveauSalon(): void
    {
        $this->formulaire = ['id' => null, 'nom' => '', 'emoji' => '💬', 'description' => '', 'type' => 'discussion', 'acces' => 'tous',
            'lecture_seule' => false, 'mode_lent_secondes' => 0, 'regles' => ''];
    }

    public function editerSalon(int $id): void
    {
        $s = collect($this->donnees['salons'] ?? [])->firstWhere('id', $id);
        if ($s) {
            $this->formulaire = collect($s)->only(['id', 'nom', 'emoji', 'description', 'type', 'acces', 'lecture_seule', 'mode_lent_secondes', 'regles'])->all();
            $this->formulaire['regles'] ??= '';
            $this->formulaire['description'] ??= '';
        }
    }

    public function fermerFormulaire(): void
    {
        $this->formulaire = null;
    }

    public function enregistrerSalon(): void
    {
        if (! $this->formulaire) {
            return;
        }
        $donnees = collect($this->formulaire)->except('id')->all();
        $r = $this->formulaire['id']
            ? Api::put('/admin/communaute/salons/'.$this->formulaire['id'], $donnees, Api::token())
            : Api::post('/admin/communaute/salons', $donnees, Api::token());
        $this->resultat($r, $this->formulaire['id'] ? 'Salon enregistré.' : 'Salon créé : il est visible par les membres.');
        if ($r['ok'] ?? false) {
            $this->formulaire = null;
        }
    }

    public function archiver(int $id, bool $archiver = true): void
    {
        $this->resultat(Api::post("/admin/communaute/salons/{$id}/".($archiver ? 'archiver' : 'restaurer'), [], Api::token()),
            $archiver ? 'Salon archivé : il n\'apparaît plus aux membres.' : 'Salon restauré.');
    }

    public function deplacer(int $id, int $sens): void
    {
        $ids = collect($this->donnees['salons'] ?? [])->pluck('id')->values()->all();
        $i = array_search($id, $ids, true);
        $j = $i + $sens;
        if ($i === false || $j < 0 || $j >= count($ids)) {
            return;
        }
        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        $this->resultat(Api::put('/admin/communaute/ordre', ['ids' => $ids], Api::token()), 'Ordre des salons enregistré.');
    }

    // ── Référents ────────────────────────────────────────────────────────

    public function gererReferents(int $salonId): void
    {
        $this->referentsSalonId = $salonId;
        $this->rechercheMembre = '';
        $this->resultats = [];
    }

    public function fermerReferents(): void
    {
        $this->referentsSalonId = null;
    }

    public function updatedRechercheMembre(): void
    {
        $this->resultats = mb_strlen(trim($this->rechercheMembre)) >= 2
            ? (Api::get('/admin/communaute/membres', ['q' => trim($this->rechercheMembre)], Api::token())['membres'] ?? [])
            : [];
    }

    public function nommer(int $userId): void
    {
        $this->resultat(Api::post("/admin/communaute/salons/{$this->referentsSalonId}/referents", ['user_id' => $userId], Api::token()), 'Référent nommé : il en est informé.');
        $this->rechercheMembre = '';
        $this->resultats = [];
    }

    public function retirerReferent(int $salonId, int $userId): void
    {
        $this->resultat(Api::delete("/admin/communaute/salons/{$salonId}/referents/{$userId}", Api::token()), 'Référent retiré.');
    }

    // ── Modération ───────────────────────────────────────────────────────

    public function voirFil(int $salonId, int $autour = 0): void
    {
        $r = Api::get("/admin/communaute/salons/{$salonId}/messages", array_filter(['autour' => $autour]), Api::token());
        $this->fil = ($r['ok'] ?? false) ? $r : null;
        if (! $this->fil) {
            $this->erreur = $r['message'] ?? 'Salon indisponible.';
        }
    }

    public function filPlusAncien(): void
    {
        if (! $this->fil || empty($this->fil['messages'])) {
            return;
        }
        $r = Api::get('/admin/communaute/salons/'.$this->fil['salon']['id'].'/messages', ['before' => $this->fil['messages'][0]['id']], Api::token());
        if ($r['ok'] ?? false) {
            $this->fil['messages'] = array_values(array_merge($r['messages'], $this->fil['messages']));
        }
    }

    public function fermerFil(): void
    {
        $this->fil = null;
    }

    public function supprimerMessage(int $id): void
    {
        $this->resultat(Api::delete("/admin/communaute/messages/{$id}", Api::token()), 'Message retiré du salon.');
        if ($this->fil) {
            foreach ($this->fil['messages'] as $i => $m) {
                if ($m['id'] === $id) {
                    $this->fil['messages'][$i]['supprime'] = true;
                    $this->fil['messages'][$i]['signale'] = false;
                }
            }
        }
    }

    public function classer(int $messageId): void
    {
        $this->resultat(Api::put("/admin/communaute/signalements/{$messageId}/classer", [], Api::token()), 'Signalement classé sans suite.');
    }

    public function sanctionner(int $salonId, int $userId, string $action, ?string $duree = null): void
    {
        $this->resultat(Api::post("/admin/communaute/salons/{$salonId}/membres/{$userId}/{$action}", ['duree' => $duree], Api::token()), 'Action enregistrée.');
    }

    /** Publie une annonce dans le canal Annonces (tous les membres sont prévenus). */
    public function publierAnnonce(): void
    {
        $texte = trim($this->annonce);
        if ($texte === '') {
            $this->erreur = 'Écrivez l\'annonce avant de la publier.';

            return;
        }
        $slug = collect($this->donnees['salons'] ?? [])->first(fn ($s) => $s['type'] === 'annonces' && ! $s['archive'])['slug'] ?? 'annonces';
        $r = Api::post('/communaute/salons/'.$slug.'/messages', ['type' => 'texte', 'body' => $texte], Api::token());
        $this->resultat($r, 'Annonce publiée : tous les membres sont prévenus.');
        if ($r['ok'] ?? false) {
            $this->annonce = '';
        }
    }

    public function enregistrerMots(): void
    {
        $this->resultat(Api::put('/admin/communaute/mots-interdits', ['texte' => $this->motsInterdits], Api::token()), 'Liste des mots interdits enregistrée.');
    }

    public function render()
    {
        return view('livewire.admin.communaute');
    }
}
