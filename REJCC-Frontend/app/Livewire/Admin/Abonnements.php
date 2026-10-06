<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use App\Support\Content\SiteRemote;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Suivi des abonnements : abonnés, délais de grâce, échéances à venir,
 * recettes, paiements et tarif. L'activation se fait uniquement par
 * paiement en ligne : l'équipe consulte, relance et règle le tarif.
 */
#[Layout('layouts.admin-light')]
class Abonnements extends Component
{
    #[Url(except: 'membres')]
    public string $onglet = 'membres';

    #[Url(except: '')]
    public string $statut = '';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    #[Url(except: 1)]
    public int $page = 1;

    public string $statutPaiement = '';

    public ?string $message = null;

    public ?string $erreur = null;

    public bool $editionTarif = false;

    public string $montant = '';

    public function setOnglet(string $o): void
    {
        $this->onglet = in_array($o, ['membres', 'paiements'], true) ? $o : 'membres';
        $this->page = 1;
        $this->recherche = '';
        $this->message = $this->erreur = null;
    }

    public function setStatut(string $s): void
    {
        $this->statut = $this->statut === $s ? '' : $s;
        $this->page = 1;
    }

    public function updatedStatut(): void
    {
        $this->page = 1;
    }

    public function updatedRecherche(): void
    {
        $this->page = 1;
    }

    public function updatedStatutPaiement(): void
    {
        $this->page = 1;
    }

    public function allerPage(int $p): void
    {
        $this->page = max(1, $p);
    }

    public function modifierTarif(int $actuel): void
    {
        $this->editionTarif = true;
        $this->montant = (string) $actuel;
        $this->erreur = null;
    }

    public function enregistrerTarif(): void
    {
        $this->message = $this->erreur = null;
        $m = (int) preg_replace('/\D/', '', $this->montant);
        $r = Api::put('/admin/abonnements/tarif', ['montant' => $m], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->erreur = $r['message'] ?? 'Enregistrement impossible.';

            return;
        }
        // Le tarif est publié avec les réglages du site (bandeaux, page Mon abonnement).
        SiteRemote::clear();
        $this->editionTarif = false;
        $this->dispatch('rj-toast', message: 'Nouveau tarif enregistré : '.number_format($m, 0, ',', ' ').' F par an. Il s\'applique aux prochains paiements.', type: 'success');
    }

    public function relancer(int $id): void
    {
        $r = Api::post("/admin/abonnements/{$id}/relancer", [], Api::token());
        $this->dispatch('rj-toast', message: $r['message'] ?? 'Relance impossible.', type: ($r['ok'] ?? false) ? 'success' : 'error');
    }

    public function render()
    {
        $token = Api::token();
        $base = Api::get('/admin/abonnements', array_filter([
            'statut' => $this->onglet === 'membres' ? $this->statut : null,
            'q' => $this->onglet === 'membres' ? trim($this->recherche) : null,
            'page' => $this->onglet === 'membres' ? $this->page : 1,
        ]), $token);
        $paiements = $this->onglet === 'paiements'
            ? Api::get('/admin/abonnements/paiements', array_filter(['statut' => $this->statutPaiement, 'q' => trim($this->recherche), 'page' => $this->page]), $token)
            : [];

        return view('livewire.admin.abonnements', [
            'stats' => $base['stats'] ?? [],
            'tarif' => (int) ($base['montant'] ?? 10000),
            'graceJours' => (int) ($base['grace_jours'] ?? 5),
            'membres' => $base['membres'] ?? [],
            'metaMembres' => $base['meta'] ?? ['current_page' => 1, 'last_page' => 1, 'total' => 0],
            'paiements' => $paiements['paiements'] ?? [],
            'metaPaiements' => $paiements['meta'] ?? ['current_page' => 1, 'last_page' => 1, 'total' => 0],
            'enforced' => (bool) (Api::get('/admin/subscription-mode', [], $token)['enforced'] ?? false),
        ]);
    }
}
