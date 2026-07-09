<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Members extends Component
{
    public string $query = '';

    public bool $showForm = false;

    public string $prenom = '';

    public string $nom = '';

    public string $email = '';

    public string $telephone = '';

    public string $password = '';

    public string $role = 'member';

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

    public function openCreate(): void
    {
        $this->reset(['prenom', 'nom', 'email', 'telephone', 'password', 'role']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        $data = $this->validate();

        $result = Api::post('/admin/members', $data, Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->addError('email', $result['message'] ?? 'Une erreur est survenue.');

            return;
        }

        $this->closeForm();
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

    public function deleteMember(int $id): void
    {
        Api::delete("/admin/members/{$id}", Api::token());
    }

    public function render()
    {
        $q = trim($this->query);

        $members = Collection::make(Api::get('/admin/members', ['q' => $q], Api::token())['members'] ?? [])
            ->map(function ($m) {
                $m['created_at'] = Carbon::parse($m['created_at']);

                return (object) $m;
            });

        return view('livewire.admin.members', ['members' => $members]);
    }
}
