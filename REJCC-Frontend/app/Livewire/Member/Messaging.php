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

    /** J'ai bloqué l'interlocuteur. */
    public bool $bloque = false;

    public bool $archivee = false;

    /** J'ai déjà signalé cette conversation (en attente de traitement). */
    public bool $signalee = false;

    /** Liste affichée : conversations archivées plutôt que la boîte de réception. */
    public bool $voirArchives = false;

    public int $nbArchives = 0;

    public ?string $info = null;

    public bool $fenetreSignalement = false;

    public string $motif = '';

    public bool $bloquerAussi = true;

    public string $body = '';

    public ?string $erreur = null;

    /** Annonce de la Marketplace dont on parle (contact depuis une annonce). */
    public ?array $annonceContexte = null;

    /** Recherche dans les conversations (nom de l'interlocuteur). */
    public string $recherche = '';

    /** Fenêtre « Nouveau message » : recherche d'un membre à qui écrire. */
    public bool $nouveau = false;

    public string $rechercheMembre = '';

    public function mount(): void
    {
        $to = request()->integer('to');

        if ($to) {
            $this->openThread($to);
            $this->rattacherAnnonce(request()->integer('annonce'));
        }
    }

    /**
     * Membre non abonné : il lit et répond aux conversations qu'on lui a
     * adressées, mais ne peut pas en démarrer (règle appliquée par l'API).
     */
    public function restreint(): bool
    {
        return ! (Api::user()->subscription_active ?? false);
    }

    public function getConversationsProperty(): array
    {
        $params = trim($this->recherche) !== '' ? ['q' => trim($this->recherche)] : [];
        if ($this->voirArchives) {
            $params['archives'] = 1;
        }

        $result = Api::get('/messages', $params, Api::token());
        $this->nbArchives = (int) ($result['archives'] ?? 0);

        return $result['conversations'] ?? [];
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
        if ($this->restreint()) {
            $this->redirectRoute('espace-membre.abonnement', navigate: true);

            return;
        }
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

    /** Contact depuis une annonce : elle est rappelée au-dessus du message et rattachée à l'envoi. */
    private function rattacherAnnonce(int $annonceId): void
    {
        if (! $annonceId || ! $this->partner) {
            return;
        }
        $l = Api::get("/marketplace/{$annonceId}", [], Api::token())['listing'] ?? null;
        if (! $l || ($l['seller']['id'] ?? null) !== $this->partner['id']) {
            return;
        }
        $this->annonceContexte = ['id' => $l['id'], 'title' => $l['title'], 'price' => $l['price'], 'photo' => $l['photo'], 'type' => $l['type']];
        if (trim($this->body) === '') {
            $this->body = "Bonjour {$this->partner['prenom']}, votre annonce m'intéresse. ";
        }
    }

    public function retirerAnnonce(): void
    {
        $this->annonceContexte = null;
    }

    public function openThread(int $userId): void
    {
        $this->activeId = $userId;
        $this->annonceContexte = null;
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
        $this->appliquerEtat($result);
        $this->info = null;
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
        $this->appliquerEtat($result);
    }

    private function appliquerEtat(array $result): void
    {
        $this->peutEcrire = (bool) ($result['peut_ecrire'] ?? true);
        $this->bloque = (bool) ($result['bloque'] ?? false);
        $this->archivee = (bool) ($result['archivee'] ?? false);
        $this->signalee = (bool) ($result['signalee'] ?? false);
    }

    public function basculerArchives(): void
    {
        $this->voirArchives = ! $this->voirArchives;
        $this->closeThread();
    }

    public function archiver(): void
    {
        if (! $this->activeId) {
            return;
        }
        $this->archivee
            ? Api::delete("/messages/{$this->activeId}/archiver", Api::token())
            : Api::post("/messages/{$this->activeId}/archiver", [], Api::token());
        $etait = $this->archivee;
        $nom = $this->partner['prenom'] ?? '';
        $this->closeThread();
        $this->info = $etait ? "La conversation avec {$nom} est de retour dans vos messages." : "Conversation avec {$nom} archivée. Elle reviendra au prochain message.";
    }

    public function basculerBlocage(): void
    {
        if (! $this->activeId) {
            return;
        }
        $this->bloque
            ? Api::delete("/messages/{$this->activeId}/bloquer", Api::token())
            : Api::post("/messages/{$this->activeId}/bloquer", [], Api::token());
        $this->rafraichir();
        $this->info = $this->bloque
            ? ($this->partner['prenom'] ?? 'Ce membre').' est bloqué : il ne peut plus vous écrire.'
            : ($this->partner['prenom'] ?? 'Ce membre').' est débloqué.';
    }

    public function ouvrirSignalement(): void
    {
        $this->fenetreSignalement = true;
        $this->motif = '';
        $this->bloquerAussi = true;
    }

    public function fermerSignalement(): void
    {
        $this->fenetreSignalement = false;
    }

    public function signaler(): void
    {
        if (! $this->activeId) {
            return;
        }
        $result = Api::post("/messages/{$this->activeId}/signaler", ['motif' => trim($this->motif), 'bloquer' => $this->bloquerAussi], Api::token());
        $this->fenetreSignalement = false;
        $this->info = $result['message'] ?? 'Une erreur est survenue.';
        $this->rafraichir();
    }

    /** Fiche du membre (fenêtre de l'annuaire), ouverte depuis l'en-tête du fil. */
    public ?array $profil = null;

    public function voirProfil(): void
    {
        if (! $this->activeId || $this->restreint()) {
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
            'listing_id' => $this->annonceContexte['id'] ?? null,
        ], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->erreur = $result['message'] ?? "Votre message n'a pas pu être envoyé. Réessayez.";

            return;
        }

        $this->body = '';
        $this->annonceContexte = null;
        $this->rafraichir();
        $this->dispatch('message-envoye');
    }

    public function render()
    {
        $conversations = $this->conversations;

        // Pastilles du menu à jour sans recharger la page (hors recherche,
        // qui ne renvoie qu'une partie des conversations).
        $nonLus = trim($this->recherche) === '' && ! $this->voirArchives ? array_sum(array_column($conversations, 'unread')) : (NavCompteurs::get()['messages'] ?? 0);
        NavCompteurs::fixer('messages', $nonLus);
        $this->dispatch('compteur-messages', n: $nonLus);

        return view('livewire.member.messaging', ['conversations' => $conversations]);
    }
}
