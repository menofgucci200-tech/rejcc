<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\Content\MembershipContent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class ProfileEditor extends Component
{
    public string $prenom = '';

    public string $nom = '';

    public string $email = '';

    public string $telephone = '';

    public string $genre = '';

    public string $ville = '';

    public string $date_naissance = '';

    public string $paroisse = '';

    public string $secteur = '';

    public string $profil = '';

    public string $organisation = '';

    public string $bio = '';

    public string $reference = '';

    public string $date_adhesion = '';

    public array $preferences = [];

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $status = 'idle';

    public string $passwordStatus = 'idle';

    protected function preferenceLabels(): array
    {
        return [
            'notifications_email' => ['label' => 'Notifications par e-mail', 'detail' => 'Rappels de formation et événements'],
            'rappels_quotidiens' => ['label' => 'Rappels quotidiens', 'detail' => 'Un rappel pour continuer votre parcours'],
            'visibilite_profil' => ['label' => 'Visibilité du profil', 'detail' => 'Visible par les autres membres du réseau'],
            'newsletter' => ['label' => 'Newsletter REJCC', 'detail' => 'Actualités mensuelles du réseau'],
            'telechargement_hors_ligne' => ['label' => 'Téléchargement hors-ligne automatique', 'detail' => 'Enregistrer les nouveaux modules pour un accès sans connexion'],
        ];
    }

    public function mount(): void
    {
        $user = Api::user();
        $this->prenom = $user->prenom ?? '';
        $this->nom = $user->nom ?? '';
        $this->email = $user->email ?? '';
        $this->telephone = $user->telephone ?? '';
        $this->genre = $user->genre ?? '';
        $this->ville = $user->ville ?? '';
        $this->date_naissance = $user->date_naissance ?? '';
        $this->paroisse = $user->paroisse ?? '';
        $this->secteur = $user->secteur ?? '';
        $this->profil = $user->profil ?? '';
        $this->organisation = $user->organisation ?? '';
        $this->bio = $user->bio ?? '';
        $this->reference = $user->reference ?? '';
        $this->date_adhesion = $user->date_adhesion ?? '';
        $this->preferences = (array) ($user->preferences ?? []);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'prenom' => 'sometimes|string|min:2|max:80',
            'nom' => 'sometimes|string|min:2|max:80',
            'telephone' => ['sometimes', 'regex:/^[0-9]{10}$/'],
            'genre' => 'nullable|in:Homme,Femme',
            'ville' => 'nullable|string|max:80',
            'date_naissance' => 'nullable|date',
            'paroisse' => 'nullable|string|max:150',
            'secteur' => 'nullable|string|max:100',
            'profil' => 'nullable|in:etudiant,porteur,entrepreneur',
            'organisation' => 'nullable|string|max:120',
            'bio' => 'nullable|string|max:600',
        ]);

        $result = Api::put('/auth/profile', $validated, Api::token());

        if ($result['ok'] ?? false) {
            session(['api_user' => $result['user']]);
            $this->status = 'saved';
        }
    }

    public function togglePreference(string $key): void
    {
        $this->preferences[$key] = ! ($this->preferences[$key] ?? false);

        $result = Api::put('/auth/preferences', ['preferences' => $this->preferences], Api::token());

        if ($result['ok'] ?? false) {
            $this->preferences = $result['preferences'];
            $user = session('api_user');
            $user['preferences'] = $result['preferences'];
            session(['api_user' => $user]);
        }
    }

    public function updatePassword(): void
    {
        $this->passwordStatus = 'idle';

        $this->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $result = Api::put('/auth/password', [
            'current_password' => $this->current_password,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->addError('current_password', $result['message'] ?? 'Une erreur est survenue.');

            return;
        }

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->passwordStatus = 'saved';
    }

    public function render()
    {
        $preferences = [];
        foreach ($this->preferenceLabels() as $key => $meta) {
            $on = (bool) ($this->preferences[$key] ?? false);
            $preferences[] = [
                'key' => $key,
                'label' => $meta['label'],
                'detail' => $meta['detail'],
                'on' => $on,
            ];
        }

        return view('livewire.member.profile-editor', [
            'profiles' => MembershipContent::profiles(),
            'preferenceRows' => $preferences,
        ]);
    }
}
