<?php

namespace App\Livewire\Admin;

use App\Support\AdminSections;
use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Inscription extends Component
{
    public string $type = 'member';

    public string $prenom = '';

    public string $nom = '';

    public string $email = '';

    public string $telephone = '';

    public string $password = '';

    public bool $accesComplet = true;

    /** Sections cochées pour un admin à accès restreint. */
    public array $sections = [];

    public ?string $message = null;

    protected function rules(): array
    {
        return [
            'type' => 'required|in:member,mentor,admin',
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:150',
            'telephone' => ['required', 'regex:/^[0-9]{10}$/'],
            'password' => 'required|string|min:8',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->type === 'admin' && ! $this->accesComplet && $this->sections === []) {
            $this->addError('sections', 'Choisissez au moins une section, ou activez l\'accès complet.');

            return;
        }

        $data = [
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'password' => $this->password,
            'role' => $this->type,
        ];

        if ($this->type === 'admin' && ! $this->accesComplet) {
            $data['permissions'] = array_values($this->sections);
        }

        $result = Api::post('/admin/members', $data, Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->addError('email', $result['message'] ?? 'Une erreur est survenue.');

            return;
        }

        $labels = ['member' => 'Membre', 'mentor' => 'Mentor', 'admin' => 'Administrateur'];
        $this->message = "{$labels[$this->type]} « {$this->prenom} {$this->nom} » créé avec succès.";
        $this->reset(['prenom', 'nom', 'email', 'telephone', 'password', 'sections']);
        $this->accesComplet = true;
    }

    public function render()
    {
        return view('livewire.admin.inscription', [
            'sectionsDisponibles' => AdminSections::SECTIONS,
        ]);
    }
}
