<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Livewire\Attributes\Layout;
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

    public function render()
    {
        return view('livewire.member.mentorat', [
            'estMentor' => $this->estMentor(),
            'user' => Api::user(),
        ]);
    }
}
