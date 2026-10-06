<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Abonnement annuel (tarif réglé par l'administration) donnant accès aux
 * fonctionnalités premium. Paiement en ligne uniquement via CinetPay (Wave,
 * Orange Money, MTN, Moov, carte), délai de grâce après l'échéance, reçus
 * téléchargeables, possibilité d'offrir l'abonnement à un autre membre.
 */
#[Layout('layouts.member-light')]
class Abonnement extends Component
{
    /** Retour de la page de paiement : [type, titre, texte]. */
    public ?array $retour = null;

    // Offrir un abonnement
    public bool $offrir = false;

    public string $recherche = '';

    public function mount(): void
    {
        $ref = request()->query('ref');

        if ($ref && preg_match('/^[A-Za-z0-9\-]+$/', $ref)) {
            // Retour de la page de paiement CinetPay : vérification immédiate
            // auprès de l'opérateur plutôt que d'attendre le webhook.
            $status = Api::get('/subscription/status', ['ref' => $ref], Api::token());
            $p = collect($status['history'] ?? [])->firstWhere('reference', $ref);
            $this->retour = match ($p['statut'] ?? null) {
                'success' => $p['offert']
                    ? ['success', 'Merci pour votre générosité !', ($p['pour'] ?? 'Ce membre')." est désormais abonné(e) jusqu'au ".$this->date($p['periode_fin']).'. Un reçu est disponible ci-dessous.']
                    : ['success', 'Paiement confirmé : votre abonnement est actif', "Il est valable jusqu'au ".$this->date($p['periode_fin']).'. Votre reçu est disponible ci-dessous.'],
                'failed' => ['error', "Le paiement n'a pas abouti", "Aucun montant n'a été débité. Vous pouvez relancer le paiement quand vous le souhaitez."],
                'pending' => ['pending', 'Paiement en cours de confirmation', "L'opérateur n'a pas encore confirmé votre paiement. Cela prend parfois quelques minutes : utilisez « Vérifier mon paiement » ci-dessous."],
                default => null,
            };
        }

        // Le statut d'abonnement en session (utilisé pour verrouiller les pages
        // premium) peut dater de la dernière connexion : on le resynchronise à
        // chaque visite de cette page pour refléter tout paiement récent.
        $this->syncUser();
    }

    private function date(?string $d): string
    {
        return $d ? \Carbon\Carbon::parse($d)->translatedFormat('j F Y') : '';
    }

    private function syncUser(): void
    {
        $result = Api::get('/auth/me', [], Api::token());
        if ($result['ok'] ?? false) {
            session(['api_user' => $result['user']]);
        }
    }

    private function lancer(array $donnees): void
    {
        $result = Api::post('/subscription/pay', $donnees, Api::token());

        if (! ($result['ok'] ?? false) || empty($result['payment_url'])) {
            $this->dispatch('rj-toast', message: $result['message'] ?? "Impossible d'initier le paiement pour le moment.", type: 'error');

            return;
        }

        $this->redirect($result['payment_url'], navigate: false);
    }

    public function payer(): void
    {
        $this->lancer([]);
    }

    public function offrirA(int $id): void
    {
        $this->lancer(['beneficiaire_id' => $id]);
    }

    public function verifier(string $ref): void
    {
        $r = Api::post('/subscription/verifier/'.rawurlencode($ref), [], Api::token());
        $type = match ($r['statut'] ?? null) {
            'success' => 'success',
            'failed' => 'error',
            default => 'info',
        };
        $this->dispatch('rj-toast', message: $r['message'] ?? 'Vérification impossible pour le moment.', type: $type);
        $this->retour = null;
        if (($r['statut'] ?? null) === 'success') {
            $this->syncUser();
        }
    }

    public function basculerOffrir(): void
    {
        $this->offrir = ! $this->offrir;
        $this->recherche = '';
    }

    public function render()
    {
        $status = Api::get('/subscription/status', [], Api::token());
        $q = trim($this->recherche);
        $membres = $this->offrir && mb_strlen($q) >= 3
            ? (Api::get('/subscription/beneficiaires', ['q' => $q], Api::token())['membres'] ?? [])
            : [];

        return view('livewire.member.abonnement', [
            's' => $status,
            'active' => (bool) ($status['active'] ?? false),
            'grace' => (bool) ($status['grace'] ?? false),
            // Abonnements obligatoires ? (interrupteur du tableau de bord admin)
            'enforced' => (bool) ($status['enforced'] ?? true),
            'exempt' => (bool) ($status['exempt'] ?? false),
            'role' => $status['role'] ?? 'member',
            'amount' => (int) ($status['amount'] ?? \App\Support\Tarif::abonnement()),
            'enAttente' => $status['en_attente'] ?? null,
            'history' => $status['history'] ?? [],
            'membres' => $membres,
        ]);
    }
}
