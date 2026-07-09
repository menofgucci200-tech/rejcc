<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Members extends Component
{
    public string $filtre = 'tous';

    public bool $showForm = false;

    public string $prenom = '';

    public string $nom = '';

    public string $email = '';

    public string $telephone = '';

    public string $password = '';

    public string $role = 'member';

    public ?string $messageCreation = null;

    public array $verifStatuts = ['attente', 'attente', 'attente', 'attente'];

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    public function toggleFormulaire(): void
    {
        $this->showForm = ! $this->showForm;
        $this->messageCreation = null;
    }

    protected function rules(): array
    {
        return [
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:150',
            'telephone' => ['required', 'regex:/^[0-9]{10}$/'],
            'password' => 'required|string|min:8',
            'role' => 'required|in:member,admin',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        $result = Api::post('/admin/members', $data, Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->addError('email', $result['message'] ?? 'Une erreur est survenue.');

            return;
        }

        $this->reset(['prenom', 'nom', 'email', 'telephone', 'password']);
        $this->role = 'member';
        $this->messageCreation = 'Compte membre créé avec succès.';
        $this->showForm = false;
    }

    public function validerVerif(int $index): void
    {
        $this->verifStatuts[$index] = 'valide';
    }

    public function rejeterVerif(int $index): void
    {
        $this->verifStatuts[$index] = 'rejete';
    }

    public function toggleRole(int $id): void
    {
        $members = Api::get('/admin/members', [], Api::token())['members'] ?? [];
        $member = Collection::make($members)->firstWhere('id', $id);

        if (! $member) {
            return;
        }

        $newRole = $member['role'] === 'admin' ? 'member' : 'admin';
        Api::put("/admin/members/{$id}", ['role' => $newRole], Api::token());
    }

    public function toggleStatut(int $id): void
    {
        $members = Api::get('/admin/members', [], Api::token())['members'] ?? [];
        $member = Collection::make($members)->firstWhere('id', $id);

        if (! $member) {
            return;
        }

        Api::put("/admin/members/{$id}", ['is_active' => ! ($member['is_active'] ?? true)], Api::token());
    }

    protected function verifications(): array
    {
        $data = [
            ['nom' => 'Yves Kacou', 'document' => 'CNI', 'date' => 'il y a 1 jour', 'initiales' => 'YK', 'from' => '#031D59', 'to' => '#4F6FBF'],
            ['nom' => 'Aïcha Traoré', 'document' => 'Passeport', 'date' => 'il y a 2 jours', 'initiales' => 'AT', 'from' => '#AC0100', 'to' => '#D95B5A'],
            ['nom' => 'Grace Amani', 'document' => 'CNI', 'date' => 'il y a 3 jours', 'initiales' => 'GA', 'from' => '#4F6FBF', 'to' => '#8FB0FF'],
            ['nom' => 'Bertin Yao', 'document' => 'Attestation', 'date' => 'il y a 4 jours', 'initiales' => 'BY', 'from' => '#031D59', 'to' => '#4F6FBF'],
        ];

        return array_map(function ($v, $i) {
            $s = $this->verifStatuts[$i];
            $info = $s === 'valide' ? ['#22A85A', '#EAF6EE', 'Validé'] : ($s === 'rejete' ? ['#AC0100', '#F9E9E9', 'Rejeté'] : ['#F5A623', '#FCF1DD', 'En attente']);
            $v['statutColor'] = $info[0];
            $v['statutBg'] = $info[1];
            $v['statutLabel'] = $info[2];
            $v['enAttente'] = $s === 'attente';
            $v['index'] = $i;

            return $v;
        }, $data, array_keys($data));
    }

    public function render()
    {
        $members = Collection::make(Api::get('/admin/members', [], Api::token())['members'] ?? [])
            ->when($this->filtre === 'actifs', fn ($c) => $c->filter(fn ($m) => $m['is_active'] ?? true))
            ->when($this->filtre === 'suspendus', fn ($c) => $c->filter(fn ($m) => ! ($m['is_active'] ?? true)))
            ->map(fn ($m) => (object) $m)
            ->values();

        return view('livewire.admin.members', [
            'members' => $members,
            'verifications' => $this->verifications(),
        ]);
    }
}
