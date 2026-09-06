<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Abonnement annuel (10 000 F) donnant accès aux fonctionnalités premium
 * (carte membre, annuaire, messagerie, publication sur la marketplace, projets).
 * Paiement encaissé via CinetPay (Wave / Orange Money / MTN / Moov / carte).
 */
#[Layout('layouts.member-light')]
class Abonnement extends Component
{
    public bool $loading = false;

    public ?string $erreur = null;

    public function mount(): void
    {
        $ref = request()->query('ref');

        if ($ref) {
            // Retour de la page de paiement CinetPay : on force une vérification
            // immédiate plutôt que d'attendre le webhook.
            $result = Api::get('/subscription/status', ['ref' => $ref], Api::token());

            if ($result['active'] ?? false) {
                session()->flash('abonnement_message', 'Paiement confirmé ! Votre abonnement est actif.');
            }
        }

        // Le statut d'abonnement en session (utilisé pour verrouiller les pages
        // premium) peut dater de la dernière connexion : on le resynchronise à
        // chaque visite de cette page pour refléter tout paiement récent.
        $this->syncUser();
    }

    private function syncUser(): void
    {
        $result = Api::get('/auth/me', [], Api::token());
        if ($result['ok'] ?? false) {
            session(['api_user' => $result['user']]);
        }
    }

    public function payer(): void
    {
        $this->loading = true;
        $this->erreur = null;

        $result = Api::post('/subscription/pay', [], Api::token());

        if (! ($result['ok'] ?? false) || empty($result['payment_url'])) {
            $this->loading = false;
            $this->erreur = $result['message'] ?? "Impossible d'initier le paiement pour le moment.";

            return;
        }

        $this->redirect($result['payment_url'], navigate: false);
    }

    public function render()
    {
        $status = Api::get('/subscription/status', [], Api::token());

        return view('livewire.member.abonnement', [
            'active' => $status['active'] ?? false,
            'expiresAt' => $status['expires_at'] ?? null,
            'amount' => $status['amount'] ?? 10000,
            'history' => $status['history'] ?? [],
        ]);
    }
}
