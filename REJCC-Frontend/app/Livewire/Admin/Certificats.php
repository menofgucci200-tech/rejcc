<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Registre des certificats et attestations : suivi, révocation motivée,
 * correction (nouvelle délivrance), demandes des membres, signataires et
 * cachet imprimés sur les nouveaux certificats, aperçu du modèle.
 */
#[Layout('layouts.admin-light')]
class Certificats extends Component
{
    use WithFileUploads;

    #[Url(except: 'registre')]
    public string $onglet = 'registre';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $statut = '';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    #[Url(except: 1)]
    public int $page = 1;

    #[Url(as: 'certificat', except: null)]
    public ?int $ouvert = null;

    public ?string $message = null;

    public ?string $erreur = null;

    // Actions sur un certificat
    public string $action = ''; // revoquer | corriger | refuser

    public string $motif = '';

    public string $nom = '';

    public string $titre = '';

    // Réglages
    public string $lieu = 'Abidjan';

    public array $signataires = [];

    public array $signatureUploads = [null, null];

    public $cachetUpload = null;

    public bool $supprimerCachet = false;

    public function mount(): void
    {
        $this->chargerReglages();
    }

    private function chargerReglages(): void
    {
        $r = Api::get('/admin/certificats/reglages', [], Api::token())['reglages'] ?? [];
        $this->lieu = $r['lieu'] ?? 'Abidjan';
        $this->signataires = collect($r['signataires'] ?? [])->map(fn ($s, $i) => [
            'nom' => $s['nom'] ?? '', 'fonction' => $s['fonction'] ?? '',
            'apercu' => $r['signatures_apercu'][$i] ?? null, 'supprimer' => false,
        ])->pad(2, ['nom' => '', 'fonction' => '', 'apercu' => null, 'supprimer' => false])->take(2)->all();
        $this->signataires[0]['cachet_apercu'] = $r['cachet_apercu'] ?? null;
        $this->signatureUploads = [null, null];
        $this->cachetUpload = null;
        $this->supprimerCachet = false;
    }

    public function setOnglet(string $o): void
    {
        $this->onglet = in_array($o, ['registre', 'reglages'], true) ? $o : 'registre';
        $this->message = $this->erreur = null;
    }

    public function filtrer(string $champ, string $valeur): void
    {
        if (in_array($champ, ['type', 'statut'], true)) {
            $this->{$champ} = $this->{$champ} === $valeur ? '' : $valeur;
            $this->page = 1;
        }
    }

    public function updatedRecherche(): void
    {
        $this->page = 1;
    }

    public function ouvrir(int $id): void
    {
        $this->ouvert = $id;
        $this->action = '';
        $this->message = $this->erreur = null;
    }

    public function fermer(): void
    {
        $this->ouvert = null;
        $this->action = '';
    }

    public function preparer(string $action, string $nom = '', string $titre = ''): void
    {
        $this->action = $action;
        $this->motif = '';
        $this->nom = $nom;
        $this->titre = $titre;
        $this->erreur = null;
    }

    public function revoquer(): void
    {
        $this->resultat(Api::post("/admin/certificates/{$this->ouvert}/revoquer", ['motif' => trim($this->motif)], Api::token()),
            'Certificat révoqué : il apparaît désormais comme tel lors des vérifications, et le membre est prévenu.');
    }

    public function corriger(): void
    {
        $r = Api::post("/admin/certificates/{$this->ouvert}/reemettre", [
            'nom' => trim($this->nom), 'titre' => trim($this->titre) ?: null, 'motif' => trim($this->motif),
        ], Api::token());
        if ($this->resultat($r, 'Certificat corrigé : un nouveau certificat (nouveau code) est délivré, l\'ancien renvoie vers lui.')) {
            $this->ouvert = $r['certificate']['id'] ?? $this->ouvert;
        }
    }

    public function refuserCorrection(): void
    {
        $this->resultat(Api::post("/admin/certificates/{$this->ouvert}/correction/refuser", ['reponse' => trim($this->motif)], Api::token()),
            'Demande classée : le membre a reçu votre réponse.');
    }

    private function resultat(array $r, string $succes): bool
    {
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Une erreur est survenue.';

            return false;
        }
        $this->action = '';
        $this->erreur = null;
        $this->message = $succes;
        AdminNav::oublier();

        return true;
    }

    // ── Réglages ─────────────────────────────────────────────────────────

    public function enregistrerReglages(): void
    {
        $this->validate([
            'signatureUploads.*' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'cachetUpload' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ], [
            'signatureUploads.*.max' => 'La signature ne doit pas dépasser 1 Mo.',
            'signatureUploads.*.mimes' => 'Signature : image PNG (fond transparent conseillé) ou JPG.',
            'cachetUpload.max' => 'Le cachet ne doit pas dépasser 1 Mo.',
            'cachetUpload.mimes' => 'Cachet : image PNG (fond transparent conseillé) ou JPG.',
        ]);
        $b64 = fn ($f) => $f ? 'data:'.$f->getMimeType().';base64,'.base64_encode($f->get()) : null;
        $r = Api::put('/admin/certificats/reglages', [
            'lieu' => trim($this->lieu),
            'signataires' => collect($this->signataires)->map(fn ($s, $i) => array_filter([
                'nom' => trim($s['nom'] ?? ''), 'fonction' => trim($s['fonction'] ?? ''),
                'signature_image' => $b64($this->signatureUploads[$i] ?? null),
                'supprimer_signature' => ! empty($s['supprimer']),
            ], fn ($v) => $v !== null && $v !== false))->values()->all(),
            'cachet_image' => $b64($this->cachetUpload),
            'supprimer_cachet' => $this->supprimerCachet,
        ], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Une erreur est survenue.';

            return;
        }
        $this->erreur = null;
        $this->message = 'Réglages enregistrés : ils s\'appliquent aux prochains certificats délivrés.';
        $this->chargerReglages();
    }

    public function render()
    {
        $data = $this->onglet === 'registre'
            ? Api::get('/admin/certificates', array_filter([
                'type' => $this->type, 'statut' => $this->statut, 'q' => trim($this->recherche), 'page' => $this->page > 1 ? $this->page : null,
            ]), Api::token())
            : [];
        $detail = $this->ouvert ? Api::get("/admin/certificates/{$this->ouvert}", [], Api::token()) : null;

        return view('livewire.admin.certificats', [
            'certificats' => Collection::make($data['certificates'] ?? []),
            'meta' => $data['meta'] ?? ['current_page' => 1, 'last_page' => 1, 'total' => 0],
            'stats' => $data['stats'] ?? Api::get('/admin/certificates', [], Api::token())['stats'] ?? [],
            'detail' => ($detail['ok'] ?? false) ? $detail : null,
        ]);
    }
}
