<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\ProfileCompletion;
use App\Support\Content\MembershipContent;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.member-light')]
class ProfileEditor extends Component
{
    use WithFileUploads;

    public string $photo = '';

    public string $piece_identite = '';

    public $photoFile = null;

    public $idFile = null;

    public ?string $mediaMessage = null;

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

    // Page biographique publique (QR code de la carte)
    public string $titre = '';

    public string $diocese = '';

    public array $competences = [];

    public string $nouvelleCompetence = '';

    public array $parcours = [];

    public array $liens = ['site' => '', 'linkedin' => '', 'facebook' => '', 'instagram' => ''];

    public string $bioStatus = 'idle';

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
            'apparaitre_annuaire' => ['label' => "Apparaître dans l'annuaire", 'detail' => 'Les membres abonnés peuvent vous trouver, voir votre fiche et vous écrire'],
            'visibilite_profil' => ['label' => 'Coordonnées visibles par les membres', 'detail' => 'Téléphone et e-mail affichés sur votre fiche (annuaire, groupes) — masqués par défaut'],
            'coordonnees_publiques' => ['label' => 'Coordonnées sur ma page publique', 'detail' => 'Téléphone et e-mail visibles par toute personne qui scanne le QR code de votre carte'],
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
        $this->titre = $user->titre ?? '';
        $this->diocese = $user->diocese ?? '';
        $this->competences = array_values((array) ($user->competences ?? []));
        $this->parcours = array_map(
            fn ($p) => ['periode' => (string) ($p['periode'] ?? ''), 'titre' => (string) ($p['titre'] ?? ''), 'structure' => (string) ($p['structure'] ?? '')],
            array_values((array) ($user->parcours ?? [])),
        );
        $this->liens = array_merge($this->liens, array_map('strval', (array) ($user->liens ?? [])));
        $this->reference = $user->reference ?? '';
        $this->date_adhesion = $user->date_adhesion ?? '';
        $this->photo = $user->photo ?? '';
        $this->piece_identite = $user->piece_identite ?? '';
        $this->preferences = (array) ($user->preferences ?? []);
    }

    public function updatedPhotoFile(): void
    {
        $this->validate(['photoFile' => 'image|max:2048'], [
            'photoFile.image' => 'Choisissez une image (JPG, PNG, WebP…).',
            'photoFile.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ], ['photoFile' => 'photo']);
        $this->photo = Storage::disk('uploads')->url($this->photoFile->store('membres/photos', 'uploads'));
        $this->photoFile = null;
        $this->persistMedia('Photo mise à jour.', 'photoFile');
    }

    public function updatedIdFile(): void
    {
        $this->validate(['idFile' => 'file|max:5120|mimes:jpg,jpeg,png,webp,pdf'], [
            'idFile.max' => 'La pièce d\'identité ne doit pas dépasser 5 Mo.',
            'idFile.mimes' => 'Formats acceptés : JPG, PNG, WebP ou PDF.',
        ], ['idFile' => 'pièce d\'identité']);
        $this->piece_identite = Storage::disk('uploads')->url($this->idFile->store('membres/pieces', 'uploads'));
        $this->idFile = null;
        $this->persistMedia('Pièce d\'identité enregistrée.', 'idFile');
    }

    public function removePhoto(): void
    {
        $this->photo = '';
        $this->persistMedia('Photo retirée.');
    }

    private function persistMedia(string $message, string $errorKey = 'photoFile'): void
    {
        $result = Api::put('/auth/profile', [
            'photo' => $this->photo ?: null,
            'piece_identite' => $this->piece_identite ?: null,
        ], Api::token());

        if ($result['ok'] ?? false) {
            session(['api_user' => $result['user']]);
            $this->mediaMessage = $message;
        } else {
            $this->addError($errorKey, $result['message'] ?? 'L\'enregistrement a échoué. Réessayez.');
        }
    }

    /** Complétion du profil (calcul partagé avec le tableau de bord). */
    protected function completion(): int
    {
        return ProfileCompletion::percent($this->completionFields());
    }

    /** Valeurs en cours de saisie, pour que l'indicateur suive le formulaire. */
    protected function completionFields(): array
    {
        return array_map(fn (string $key) => $this->{$key}, array_combine(array_keys(ProfileCompletion::FIELDS), array_keys(ProfileCompletion::FIELDS)));
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
        ]);

        $result = Api::put('/auth/profile', $validated, Api::token());

        if ($result['ok'] ?? false) {
            session(['api_user' => $result['user']]);
            $this->status = 'saved';
        }
    }

    public function ajouterCompetence(): void
    {
        $c = trim($this->nouvelleCompetence);
        if (mb_strlen($c) >= 2 && count($this->competences) < 15 && ! in_array($c, $this->competences, true)) {
            $this->competences[] = mb_substr($c, 0, 40);
        }
        $this->nouvelleCompetence = '';
    }

    public function retirerCompetence(int $i): void
    {
        unset($this->competences[$i]);
        $this->competences = array_values($this->competences);
    }

    public function ajouterEtape(): void
    {
        if (count($this->parcours) < 8) {
            $this->parcours[] = ['periode' => '', 'titre' => '', 'structure' => ''];
        }
    }

    public function retirerEtape(int $i): void
    {
        unset($this->parcours[$i]);
        $this->parcours = array_values($this->parcours);
    }

    public function saveBio(): void
    {
        $this->bioStatus = 'idle';

        $this->validate([
            'titre' => 'nullable|string|max:120',
            'diocese' => 'nullable|string|max:120',
            'bio' => 'nullable|string|max:1500',
            'competences' => 'array|max:15',
            'parcours' => 'array|max:8',
            'parcours.*.periode' => 'nullable|string|max:40',
            'parcours.*.titre' => 'required|string|max:100',
            'parcours.*.structure' => 'nullable|string|max:120',
            'liens.*' => 'nullable|url|max:300',
        ], [
            'parcours.*.titre.required' => 'Indiquez le poste ou la réalisation (ou retirez la ligne).',
            'liens.*.url' => 'Collez l\'adresse complète (https://…).',
            'bio.max' => 'La présentation ne doit pas dépasser 1 500 caractères.',
        ]);

        $result = Api::put('/auth/profile', [
            'titre' => $this->titre ?: null,
            'diocese' => $this->diocese ?: null,
            'bio' => $this->bio ?: null,
            'competences' => $this->competences,
            'parcours' => $this->parcours,
            'liens' => array_filter($this->liens),
        ], Api::token());

        if ($result['ok'] ?? false) {
            session(['api_user' => $result['user']]);
            $this->bioStatus = 'saved';
        } else {
            $this->addError('bio', $result['message'] ?? 'Une erreur est survenue.');
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
            'completion' => $this->completion(),
            'champsManquants' => ProfileCompletion::missing($this->completionFields()),
            'pagePublique' => ($code = Api::user()->code ?? null) ? url('/carte/'.$code) : null,
        ]);
    }
}
