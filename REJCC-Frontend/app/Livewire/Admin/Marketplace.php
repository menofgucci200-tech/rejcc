<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Modération de la Marketplace : valider ou refuser (motif obligatoire) les
 * annonces soumises, corriger une annonce, la retirer avec un motif, traiter
 * les signalements des membres. Le vendeur est notifié à chaque décision.
 */
#[Layout('layouts.admin-light')]
class Marketplace extends Component
{
    #[Url(except: 'en_attente')]
    public string $filtre = 'en_attente';

    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    public int $page = 1;

    public ?int $expandedId = null;

    public ?string $message = null;

    public ?string $erreur = null;

    /** Fenêtre de décision : refuser | retirer | corriger. */
    public ?string $action = null;

    public ?int $cibleId = null;

    public string $motif = '';

    // Correction
    public string $type = 'service';

    public string $title = '';

    public string $groupId = '';

    public string $description = '';

    public string $price = '';

    public string $note = '';

    public const MOTIFS_REFUS = [
        'Description trop courte ou imprécise : précisez ce que vous proposez, votre zone et vos conditions.',
        'Visuel absent ou de mauvaise qualité : ajoutez une photo nette de votre produit ou service.',
        'Catégorie inadaptée : choisissez le groupe sectoriel qui correspond à votre activité.',
        'Contenu non conforme à la charte du membre.',
        'Annonce en double avec une annonce déjà publiée.',
    ];

    public function updatedFiltre(): void
    {
        $this->page = 1;
        $this->expandedId = null;
        $this->message = null;
    }

    public function updatedRecherche(): void
    {
        $this->page = 1;
    }

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
        $this->updatedFiltre();
    }

    public function gotoPage(int $p): void
    {
        $this->page = max(1, $p);
    }

    public function toggleDetail(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    private function terminer(array $result, string $succes): void
    {
        if ($result['ok'] ?? false) {
            $this->message = $succes;
            $this->fermer();
            AdminNav::oublier();
        } else {
            $this->erreur = $result['message'] ?? 'Une erreur est survenue.';
        }
    }

    public function approve(int $id): void
    {
        $this->terminer(Api::put("/admin/marketplace/{$id}/approve", [], Api::token()), 'Annonce publiée : le vendeur est prévenu.');
    }

    public function ouvrir(string $action, int $id): void
    {
        $this->action = $action;
        $this->cibleId = $id;
        $this->motif = '';
        $this->note = '';
        $this->erreur = null;
        $this->message = null;

        if ($action === 'corriger') {
            $l = collect($this->donnees()['listings'] ?? [])->firstWhere('id', $id);
            if ($l) {
                $this->type = $l['type'];
                $this->title = $l['title'];
                $this->groupId = (string) ($l['groupe']['id'] ?? '');
                $this->description = $l['description'];
                $this->price = (string) ($l['price'] ?? '');
            }
        }
    }

    public function fermer(): void
    {
        $this->action = null;
        $this->cibleId = null;
        $this->erreur = null;
    }

    public function confirmer(): void
    {
        if (! $this->cibleId) {
            return;
        }
        $token = Api::token();

        match ($this->action) {
            'refuser' => $this->terminer(Api::put("/admin/marketplace/{$this->cibleId}/reject", ['motif' => trim($this->motif)], $token), 'Annonce refusée : le motif a été transmis au vendeur.'),
            'retirer' => $this->terminer(Api::put("/admin/marketplace/{$this->cibleId}/retirer", ['motif' => trim($this->motif)], $token), 'Annonce retirée : le vendeur est prévenu du motif.'),
            'corriger' => $this->terminer(Api::put("/admin/marketplace/{$this->cibleId}", [
                'type' => $this->type, 'title' => trim($this->title), 'group_id' => (int) $this->groupId,
                'description' => trim($this->description), 'price' => trim($this->price) ?: null, 'note' => trim($this->note) ?: null,
            ], $token), 'Annonce corrigée : le vendeur est prévenu.'),
            default => null,
        };
    }

    public function classerSignalements(int $id): void
    {
        $this->terminer(Api::put("/admin/marketplace/{$id}/signalements", [], Api::token()), 'Signalements classés sans suite.');
    }

    public function delete(int $id): void
    {
        $this->terminer(Api::delete("/admin/marketplace/{$id}", Api::token()), 'Annonce supprimée définitivement : le vendeur est prévenu.');
    }

    private function donnees(): array
    {
        return Api::get('/admin/marketplace', array_filter([
            'filtre' => $this->filtre,
            'q' => trim($this->recherche),
            'page' => $this->page > 1 ? $this->page : null,
        ]), Api::token());
    }

    public function render()
    {
        $data = $this->donnees();

        return view('livewire.admin.marketplace', [
            'ok' => $data['ok'] ?? false,
            'listings' => collect($data['listings'] ?? []),
            'meta' => $data['meta'] ?? [],
            'compteurs' => $data['compteurs'] ?? [],
            'categories' => $data['categories'] ?? [],
            'motifsRefus' => self::MOTIFS_REFUS,
        ]);
    }
}
