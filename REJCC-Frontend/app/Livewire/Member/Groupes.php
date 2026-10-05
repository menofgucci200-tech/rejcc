<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Groupes sectoriels : pôles qui regroupent les membres par domaine
 * d'activité pour des échanges ciblés et des synergies. Adhésion libre et
 * multiple (on peut suivre plusieurs pôles ou formations en parallèle),
 * en décrivant sa spécialité dans le domaine à l'adhésion.
 */
#[Layout('layouts.member-light')]
class Groupes extends Component
{
    public ?string $message = null;

    /** Groupe pour lequel le formulaire d'adhésion/modification est ouvert. */
    public ?int $formGroupId = null;

    public string $specialite = '';

    /** Services proposés, séparés par des virgules. */
    public string $services = '';

    public string $zone = '';

    public string $disponibilites = '';

    public bool $telephoneVisible = false;

    public function ouvrirFormulaire(int $id): void
    {
        $groupe = collect(Api::get('/groups', [], Api::token())['groups'] ?? [])->firstWhere('id', $id);
        $fiche = $groupe['ma_fiche'] ?? null;

        $this->formGroupId = $id;
        $this->specialite = (string) ($fiche['specialite'] ?? '');
        $this->services = implode(', ', $fiche['services'] ?? []);
        $this->zone = (string) ($fiche['zone'] ?? (\App\Support\Api::user()->ville ?? ''));
        $this->disponibilites = (string) ($fiche['disponibilites'] ?? '');
        $this->telephoneVisible = (bool) ($fiche['telephone_visible'] ?? false);
        $this->resetValidation();
    }

    public function fermerFormulaire(): void
    {
        $this->formGroupId = null;
        $this->reset(['specialite', 'services', 'zone', 'disponibilites', 'telephoneVisible']);
    }

    public function confirmerAdhesion(): void
    {
        $this->validate([
            'specialite' => 'required|string|min:10|max:600',
        ], [
            'specialite.required' => 'Décrivez votre spécialité dans ce domaine pour rejoindre le groupe.',
            'specialite.min' => 'Décrivez votre spécialité en quelques mots de plus (10 caractères minimum).',
        ]);

        $result = Api::post("/groups/{$this->formGroupId}/join", [
            'specialite' => trim($this->specialite),
            'services' => array_values(array_filter(array_map('trim', explode(',', $this->services)))),
            'zone' => trim($this->zone) ?: null,
            'disponibilites' => trim($this->disponibilites) ?: null,
            'telephone_visible' => $this->telephoneVisible,
        ], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->addError('specialite', $result['message'] ?? 'Une erreur est survenue.');

            return;
        }

        $this->message = 'Votre fiche a bien été enregistrée dans le groupe.';
        $this->fermerFormulaire();
    }

    public function quitter(int $id): void
    {
        $result = Api::post("/groups/{$id}/leave", [], Api::token());
        $this->message = ($result['ok'] ?? false) ? 'Vous avez quitté le groupe.' : ($result['message'] ?? 'Une erreur est survenue.');
    }

    public function render()
    {
        $groups = collect(Api::get('/groups', [], Api::token())['groups'] ?? []);

        return view('livewire.member.groupes', [
            'groups' => $groups,
            'mesGroupes' => $groups->where('joined', true)->values(),
        ]);
    }
}
