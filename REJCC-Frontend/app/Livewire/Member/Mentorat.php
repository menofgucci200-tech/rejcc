<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Programme de mentorat. Un mentor y gère sa fiche de mentor (et ses
 * mentorés) ; un membre y trouve un mentor et suit son accompagnement.
 */
#[Layout('layouts.member-light')]
class Mentorat extends Component
{
    // ── Fiche de mentor ──────────────────────────────────────────────────
    public string $expertises = '';

    public string $bio = '';

    public string $disponibilites = '';

    public string $format = '';

    public int $capacite = 3;

    public bool $accepte = true;

    public ?string $messageProfil = null;

    public ?string $erreurProfil = null;

    // ── Trouver un mentor (membres) ──────────────────────────────────────
    #[Url(as: 'q', except: '')]
    public string $recherche = '';

    #[Url(as: 'expertise', except: '')]
    public string $expertise = '';

    /** Mentor dont la fiche / le formulaire de demande est ouvert. */
    public ?int $mentorOuvert = null;

    public string $objectif = '';

    public string $besoin = '';

    public ?string $erreurDemande = null;

    public bool $abonnementRequis = false;

    public ?string $message = null;

    // ── Demandes reçues (mentors) ────────────────────────────────────────
    /** @var array<int, string> Mot du mentor par demande (accueil ou refus). */
    public array $reponses = [];

    public ?int $erreurPour = null;

    public function mount(): void
    {
        $mentor = Api::user()->mentor ?? null;
        if ($this->estMentor() && is_array($mentor)) {
            $this->expertises = implode(', ', $mentor['expertises'] ?? []);
            $this->bio = (string) ($mentor['bio'] ?? '');
            $this->disponibilites = (string) ($mentor['disponibilites'] ?? '');
            $this->format = (string) ($mentor['format'] ?? '');
            $this->capacite = (int) ($mentor['capacite'] ?? 3);
            $this->accepte = (bool) ($mentor['accepte'] ?? true);
        }
    }

    protected function estMentor(): bool
    {
        return (Api::user()->role ?? null) === 'mentor';
    }

    public function enregistrerProfil(): void
    {
        $this->messageProfil = $this->erreurProfil = null;
        if (! $this->estMentor()) {
            return;
        }

        $result = Api::put('/mentorat/profil', [
            'expertises' => array_values(array_filter(array_map('trim', explode(',', $this->expertises)))),
            'bio' => trim($this->bio) ?: null,
            'disponibilites' => trim($this->disponibilites) ?: null,
            'format' => $this->format ?: null,
            'capacite' => $this->capacite,
            'accepte' => $this->accepte,
        ], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->erreurProfil = $result['message'] ?? 'Enregistrement impossible, réessayez.';

            return;
        }

        // Session à jour tout de suite (sinon rafraîchie au plus tard sous 60 s).
        $user = session('api_user', []);
        $user['mentor'] = $result['mentor'];
        session(['api_user' => $user]);
        $this->expertises = implode(', ', $result['mentor']['expertises']);
        $this->messageProfil = 'Votre fiche de mentor est à jour.';
    }

    public function filtrerExpertise(string $expertise): void
    {
        $this->expertise = $this->expertise === $expertise ? '' : $expertise;
    }

    public function ouvrirMentor(int $id): void
    {
        $this->mentorOuvert = $id;
        $this->objectif = $this->besoin = '';
        $this->erreurDemande = null;
        $this->abonnementRequis = false;
    }

    public function fermerMentor(): void
    {
        $this->mentorOuvert = null;
    }

    public function demander(): void
    {
        $this->erreurDemande = null;
        $this->abonnementRequis = false;
        if (! $this->mentorOuvert) {
            return;
        }

        $result = Api::post("/mentors/{$this->mentorOuvert}/demande", [
            'objectif' => trim($this->objectif),
            'besoin' => trim($this->besoin) ?: null,
        ], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->erreurDemande = $result['message'] ?? 'Envoi impossible, réessayez.';
            $this->abonnementRequis = ($result['code'] ?? null) === 'subscription_required';

            return;
        }

        $this->mentorOuvert = null;
        $this->message = 'Demande envoyée à '.($result['mentorship']['autre']['prenom'] ?? 'votre mentor').' : une notification vous préviendra de sa réponse.';
    }

    public function annuler(int $id): void
    {
        $result = Api::post("/mentorat/{$id}/annuler", [], Api::token());
        $this->message = ($result['ok'] ?? false) ? 'Demande retirée.' : null;
        $this->erreurDemande = ($result['ok'] ?? false) ? null : ($result['message'] ?? 'Action impossible.');
    }

    protected function repondre(int $id, string $action): void
    {
        $this->message = $this->erreurDemande = null;
        $this->erreurPour = null;
        $result = Api::post("/mentorat/{$id}/{$action}", ['reponse' => trim($this->reponses[$id] ?? '')], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->erreurPour = $id;
            $this->erreurDemande = $result['message'] ?? 'Action impossible, réessayez.';

            return;
        }

        unset($this->reponses[$id]);
        $this->message = $action === 'accepter'
            ? 'Demande acceptée : une notification a été envoyée au membre.'
            : 'Réponse envoyée : une notification a été envoyée au membre.';
    }

    public function accepter(int $id): void
    {
        $this->repondre($id, 'accepter');
    }

    public function refuser(int $id): void
    {
        $this->repondre($id, 'refuser');
    }

    public function render()
    {
        $token = Api::token();
        $mesMentorats = Api::get('/mentorat', [], $token);
        $mentorats = Collection::make($mesMentorats['mentorats'] ?? []);
        $mentors = collect();
        $domaines = collect();

        if (! $this->estMentor()) {
            $result = Api::get('/mentors', array_filter(['q' => trim($this->recherche)]), $token);
            $domaines = Collection::make($result['expertises'] ?? []);
            $mentors = Collection::make($result['mentors'] ?? [])
                ->when($this->expertise !== '', fn ($c) => $c->filter(
                    fn ($m) => collect($m['mentor']['expertises'] ?? [])->contains(fn ($e) => mb_strtolower($e) === mb_strtolower($this->expertise))
                ))->values();
        }

        return view('livewire.member.mentorat', [
            'estMentor' => $this->estMentor(),
            'user' => Api::user(),
            'mentorats' => $mentorats,
            'mentors' => $mentors,
            'domaines' => $domaines,
            'mentorFiche' => $this->mentorOuvert ? $mentors->firstWhere('id', $this->mentorOuvert) : null,
            'placesRestantes' => $mesMentorats['places_restantes'] ?? null,
            'peutDemander' => (bool) (Api::user()->subscription_active ?? false),
        ]);
    }
}
