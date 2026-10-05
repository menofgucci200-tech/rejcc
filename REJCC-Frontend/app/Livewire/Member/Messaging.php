<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\NavCompteurs;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Messagerie privée : liste des conversations et fil de discussion.
 * Rafraîchie toutes les 4 s ; seuls les nouveaux messages du fil ouvert
 * sont demandés à l'API (paramètre `after`).
 */
#[Layout('layouts.member-light')]
class Messaging extends Component
{
    public ?int $activeId = null;

    public ?array $partner = null;

    public array $messages = [];

    /** Dernier de mes messages lu par l'interlocuteur (« Vu »). */
    public int $vuJusqua = 0;

    public bool $peutEcrire = true;

    public string $body = '';

    public ?string $erreur = null;

    /** Recherche dans les conversations (nom de l'interlocuteur). */
    public string $recherche = '';

    /** Fenêtre « Nouveau message » : recherche d'un membre à qui écrire. */
    public bool $nouveau = false;

    public string $rechercheMembre = '';

    public function mount(): void
    {
        if ($this->locked()) {
            return;
        }

        $to = request()->integer('to');

        if ($to) {
            $this->openThread($to);
        }
    }

    public function locked(): bool
    {
        return ! (Api::user()->subscription_active ?? false);
    }

    public function getConversationsProperty(): array
    {
        if ($this->locked()) {
            return [];
        }

        $params = trim($this->recherche) !== '' ? ['q' => trim($this->recherche)] : [];

        return Api::get('/messages', $params, Api::token())['conversations'] ?? [];
    }

    /** Membres proposés dans « Nouveau message » (recherche dans l'annuaire). */
    public function getMembresTrouvesProperty(): array
    {
        $q = trim($this->rechercheMembre);
        if (! $this->nouveau || mb_strlen($q) < 2) {
            return [];
        }
        $moi = Api::user()->id ?? 0;

        return collect(Api::get('/members', ['q' => $q], Api::token())['members'] ?? [])
            ->reject(fn ($m) => $m['id'] === $moi)->take(8)->values()->all();
    }

    public function ouvrirNouveau(): void
    {
        $this->nouveau = true;
        $this->rechercheMembre = '';
    }

    public function fermerNouveau(): void
    {
        $this->nouveau = false;
    }

    public function ecrireA(int $id): void
    {
        $this->nouveau = false;
        $this->openThread($id);
    }

    public function openThread(int $userId): void
    {
        $this->activeId = $userId;
        $this->erreur = null;
        $this->body = '';

        $result = Api::get("/messages/{$userId}", [], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->partner = null;
            $this->messages = [];
            $this->peutEcrire = false;
            $this->erreur = $result['message'] ?? 'Cette conversation est indisponible.';

            return;
        }

        $this->partner = $result['partner'];
        $this->messages = $result['messages'] ?? [];
        $this->vuJusqua = (int) ($result['vu_jusqua'] ?? 0);
        $this->peutEcrire = (bool) ($result['peut_ecrire'] ?? true);
        NavCompteurs::oublier(); // les messages ouverts sont lus
    }

    /** Rafraîchissement périodique : seuls les nouveaux messages du fil ouvert. */
    public function rafraichir(): void
    {
        if (! $this->activeId || ! $this->partner) {
            return;
        }

        $dernier = (int) (end($this->messages)['id'] ?? 0);
        $result = Api::get("/messages/{$this->activeId}", ['after' => $dernier], Api::token());
        if (! ($result['ok'] ?? false)) {
            return;
        }

        foreach ($result['messages'] ?? [] as $m) {
            $this->messages[] = $m;
        }
        $this->vuJusqua = (int) ($result['vu_jusqua'] ?? $this->vuJusqua);
        $this->peutEcrire = (bool) ($result['peut_ecrire'] ?? true);
    }

    /** Fiche du membre (fenêtre de l'annuaire), ouverte depuis l'en-tête du fil. */
    public ?array $profil = null;

    public function voirProfil(): void
    {
        if (! $this->activeId) {
            return;
        }
        $result = Api::get("/members/{$this->activeId}", [], Api::token());
        $this->profil = ($result['ok'] ?? false) ? $result['member'] : null;
    }

    public function fermerProfil(): void
    {
        $this->profil = null;
    }

    public function closeThread(): void
    {
        $this->activeId = null;
        $this->partner = null;
        $this->messages = [];
        $this->erreur = null;
    }

    public function send(): void
    {
        $this->erreur = null;
        $texte = trim($this->body);

        if (! $this->activeId || ! $this->partner) {
            return;
        }
        if ($texte === '') {
            $this->erreur = "Écrivez votre message avant de l'envoyer.";

            return;
        }
        if (mb_strlen($texte) > 2000) {
            $this->erreur = 'Votre message est trop long (2000 caractères maximum).';

            return;
        }

        $result = Api::post('/messages', [
            'recipient_id' => $this->activeId,
            'body' => $texte,
        ], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->erreur = $result['message'] ?? "Votre message n'a pas pu être envoyé. Réessayez.";

            return;
        }

        $this->body = '';
        $this->rafraichir();
        $this->dispatch('message-envoye');
    }

    public function render()
    {
        $conversations = $this->conversations;

        // Pastilles du menu à jour sans recharger la page (hors recherche,
        // qui ne renvoie qu'une partie des conversations).
        $nonLus = trim($this->recherche) === '' ? array_sum(array_column($conversations, 'unread')) : (NavCompteurs::get()['messages'] ?? 0);
        NavCompteurs::fixer('messages', $nonLus);
        $this->dispatch('compteur-messages', n: $nonLus);

        return view('livewire.member.messaging', ['conversations' => $conversations]);
    }
}
