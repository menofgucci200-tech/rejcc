<?php

namespace App\Livewire\Member;

use App\Support\Api;
use App\Support\Coffre;
use App\Support\Content\MembershipContent;
use App\Support\PiecesAnciennes;
use App\Support\ProfileCompletion;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Paramètres du membre, en onglets : profil, page publique, notifications,
 * confidentialité, sécurité et compte (affichage, données, journal, clôture).
 */
#[Layout('layouts.member-light')]
class ProfileEditor extends Component
{
    use WithFileUploads;

    public const ONGLETS = [
        'profil' => ['Profil', 'user'],
        'page' => ['Page publique', 'globe'],
        'notifications' => ['Notifications', 'bell'],
        'confidentialite' => ['Confidentialité', 'eye'],
        'securite' => ['Sécurité', 'lock'],
        'compte' => ['Mon compte', 'settings'],
    ];

    #[Url(except: 'profil')]
    public string $onglet = 'profil';

    // ── Profil ──────────────────────────────────────────────────────────
    public string $photo = '';

    public $photoFile = null;

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

    // ── Page publique ───────────────────────────────────────────────────
    public string $bio = '';

    public string $titre = '';

    public string $diocese = '';

    public array $competences = [];

    public string $nouvelleCompetence = '';

    public array $parcours = [];

    public array $liens = ['site' => '', 'linkedin' => '', 'facebook' => '', 'instagram' => ''];

    // ── Préférences, notifications ──────────────────────────────────────
    public array $preferences = [];

    public array $emailCat = [];

    public array $pushCat = [];

    public string $pauseEmails = '';

    // ── Sécurité ────────────────────────────────────────────────────────
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $deconnecterAutres = true;

    public string $nouvelEmail = '';

    public string $motDePasseEmail = '';

    // ── Compte ──────────────────────────────────────────────────────────
    public string $motifCloture = '';

    public string $motDePasseCloture = '';

    public function mount(): void
    {
        $this->onglet = isset(self::ONGLETS[$this->onglet]) ? $this->onglet : 'profil';
        $this->rafraichirUtilisateur();
        $user = Api::user();

        // Ancienne pièce d'identité publique : reprise chiffrée dans le coffre-fort.
        if (! empty($user->piece_identite) && PiecesAnciennes::importer($user, Api::token())) {
            $this->toast('Votre pièce d\'identité a été déplacée, chiffrée, dans « Mes documents personnels ».', 'info');
            $user = Api::user();
        }

        foreach (['prenom', 'nom', 'email', 'telephone', 'genre', 'ville', 'paroisse', 'secteur', 'profil', 'organisation', 'bio', 'titre', 'diocese', 'photo', 'date_naissance'] as $k) {
            $this->{$k} = (string) ($user->{$k} ?? '');
        }
        $this->competences = array_values((array) ($user->competences ?? []));
        $this->parcours = array_map(
            fn ($p) => ['periode' => (string) ($p['periode'] ?? ''), 'titre' => (string) ($p['titre'] ?? ''), 'structure' => (string) ($p['structure'] ?? '')],
            array_values((array) ($user->parcours ?? [])),
        );
        $this->liens = array_merge($this->liens, array_map('strval', (array) ($user->liens ?? [])));
        $this->chargerPreferences((array) ($user->preferences ?? []));
    }

    private function rafraichirUtilisateur(): void
    {
        $r = Api::get('/auth/me', [], Api::token());
        if ($r['ok'] ?? false) {
            session(['api_user' => $r['user']]);
        }
    }

    private function chargerPreferences(array $p): void
    {
        $this->preferences = $p;
        $this->emailCat = (array) ($p['notifications']['email'] ?? []);
        $this->pushCat = array_map('boolval', (array) ($p['notifications']['push'] ?? []));
        $this->pauseEmails = (string) ($p['notifications']['pause_emails'] ?? '');
    }

    private function toast(string $message, string $type = 'succes'): void
    {
        $this->dispatch('rj-toast', message: $message, type: $type);
    }

    public function setOnglet(string $o): void
    {
        $this->onglet = isset(self::ONGLETS[$o]) ? $o : 'profil';
        $this->resetErrorBag();
    }

    // ── Profil ──────────────────────────────────────────────────────────

    /** Photo déjà recadrée et compressée dans le navigateur (carré 600 px). */
    public function updatedPhotoFile(): void
    {
        $this->validate(['photoFile' => 'image|max:4096'], [
            'photoFile.image' => 'Choisissez une image (JPG, PNG, WebP…).',
            'photoFile.max' => 'La photo ne doit pas dépasser 4 Mo.',
        ]);
        $ancienne = $this->photo;
        $this->photo = Storage::disk('uploads')->url($this->photoFile->store('membres/photos', 'uploads'));
        $this->photoFile = null;
        if ($this->enregistrerProfil(['photo' => $this->photo], 'photoFile')) {
            $this->supprimerFichierPhoto($ancienne);
            $this->toast('Photo de profil mise à jour.');
        }
    }

    public function removePhoto(): void
    {
        $ancienne = $this->photo;
        $this->photo = '';
        if ($this->enregistrerProfil(['photo' => null], 'photoFile')) {
            $this->supprimerFichierPhoto($ancienne);
            $this->toast('Photo retirée.');
        }
    }

    private function supprimerFichierPhoto(?string $url): void
    {
        $rel = (string) str((string) $url)->after('/uploads/');
        if ($url && str_starts_with($rel, 'membres/photos/') && ! str_contains($rel, '..')) {
            Storage::disk('uploads')->delete($rel);
        }
    }

    private function enregistrerProfil(array $donnees, string $champErreur = 'profil'): bool
    {
        $r = Api::put('/auth/profile', $donnees, Api::token());
        if ($r['ok'] ?? false) {
            session(['api_user' => $r['user']]);

            return true;
        }
        $this->addError($champErreur, $r['message'] ?? 'L\'enregistrement a échoué. Réessayez.');
        $this->toast($r['message'] ?? 'L\'enregistrement a échoué. Réessayez.', 'erreur');

        return false;
    }

    /** Complétion du profil (calcul partagé avec le tableau de bord). */
    protected function completionFields(): array
    {
        return array_map(fn (string $key) => $this->{$key}, array_combine(array_keys(ProfileCompletion::FIELDS), array_keys(ProfileCompletion::FIELDS)));
    }

    public function save(): void
    {
        $this->telephone = preg_replace('/\D/', '', $this->telephone);
        $validated = $this->validate([
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'telephone' => ['required', 'regex:/^[0-9]{10}$/'],
            'genre' => 'nullable|in:Homme,Femme',
            'ville' => 'nullable|string|max:80',
            'date_naissance' => 'nullable|date|before:-12 years|after:1930-01-01',
            'paroisse' => 'nullable|string|max:150',
            'secteur' => 'nullable|string|max:100',
            'profil' => 'nullable|in:etudiant,porteur,entrepreneur',
            'organisation' => 'nullable|string|max:120',
        ], [
            'prenom.required' => 'Indiquez votre prénom.', 'nom.required' => 'Indiquez votre nom.',
            'prenom.min' => 'Le prénom doit contenir au moins 2 lettres.', 'nom.min' => 'Le nom doit contenir au moins 2 lettres.',
            'telephone.required' => 'Indiquez votre numéro de téléphone.',
            'telephone.regex' => 'Le numéro doit comporter 10 chiffres (ex. 07 01 02 03 04).',
            'date_naissance.before' => 'Vérifiez votre date de naissance.', 'date_naissance.after' => 'Vérifiez votre date de naissance.',
        ]);

        if ($this->enregistrerProfil(array_map(fn ($v) => $v === '' ? null : $v, $validated))) {
            $this->dispatch('rj-enregistre');
            $this->toast('Vos informations sont enregistrées.');
        }
    }

    // ── Page publique ───────────────────────────────────────────────────

    public function ajouterCompetence(): void
    {
        $c = trim($this->nouvelleCompetence);
        if (mb_strlen($c) >= 2 && count($this->competences) < 15 && ! in_array(mb_strtolower($c), array_map('mb_strtolower', $this->competences), true)) {
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

        if ($this->enregistrerProfil([
            'titre' => $this->titre ?: null,
            'diocese' => $this->diocese ?: null,
            'bio' => $this->bio ?: null,
            'competences' => $this->competences,
            'parcours' => $this->parcours,
            'liens' => array_filter($this->liens),
        ], 'bio')) {
            $this->dispatch('rj-enregistre');
            $this->toast('Votre page publique est à jour.');
        }
    }

    // ── Préférences (confidentialité, newsletter, affichage) ────────────

    public function togglePreference(string $key): void
    {
        if (! in_array($key, ['apparaitre_annuaire', 'visibilite_profil', 'coordonnees_publiques', 'newsletter', 'texte_grand'], true)) {
            return;
        }
        $valeur = ! ($this->preferences[$key] ?? false);
        $r = Api::put('/auth/preferences', ['preferences' => [$key => $valeur]], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->toast($r['message'] ?? 'Réglage non enregistré, réessayez.', 'erreur');

            return;
        }
        $this->chargerPreferences($r['preferences']);
        $user = session('api_user');
        $user['preferences'] = $r['preferences'];
        session(['api_user' => $user]);
        if ($key === 'texte_grand') {
            $this->dispatch('rj-texte-grand', actif: $valeur);
        }
        $this->toast('Réglage enregistré.');
    }

    // ── Notifications ───────────────────────────────────────────────────

    public function enregistrerNotifications(): void
    {
        $r = Api::put('/auth/notifications', [
            'email' => $this->emailCat,
            'push' => array_map('boolval', $this->pushCat),
            'pause_emails' => $this->pauseEmails ?: null,
        ], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->addError('pauseEmails', $r['message'] ?? 'Réglages non enregistrés.');
            $this->toast($r['message'] ?? 'Réglages non enregistrés.', 'erreur');

            return;
        }
        $this->resetErrorBag('pauseEmails');
        $this->emailCat = $r['reglages']['email'];
        $this->pushCat = $r['reglages']['push'];
        $this->pauseEmails = (string) ($r['reglages']['pause_emails'] ?? '');
        $this->toast('Vos préférences de notifications sont enregistrées.');
    }

    public function reprendreEmails(): void
    {
        $this->pauseEmails = '';
        $this->enregistrerNotifications();
    }

    /** Appareil abonné aux notifications (envoyé par le navigateur). */
    public function enregistrerAppareil(array $abonnement): void
    {
        $r = Api::post('/auth/push', $abonnement, Api::token());
        ($r['ok'] ?? false)
            ? $this->toast('Notifications activées sur cet appareil.')
            : $this->toast($r['message'] ?? 'Activation impossible sur cet appareil.', 'erreur');
    }

    public function retirerAppareil(?string $endpoint = null): void
    {
        Api::delete('/auth/push', Api::token(), array_filter(['endpoint' => $endpoint]));
        $this->toast('Notifications désactivées sur cet appareil.', 'info');
    }

    public function essaiPush(): void
    {
        $r = Api::post('/auth/push/essai', [], Api::token());
        $this->toast($r['message'] ?? 'Envoi impossible.', ($r['ok'] ?? false) ? 'succes' : 'erreur');
    }

    // ── Sécurité ────────────────────────────────────────────────────────

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Za-zÀ-ÿ]/', 'regex:/[0-9]/'],
        ], [
            'current_password.required' => 'Saisissez votre mot de passe actuel.',
            'password.required' => 'Choisissez un nouveau mot de passe.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.regex' => 'Le mot de passe doit contenir au moins une lettre et un chiffre.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ]);

        $r = Api::put('/auth/password', [
            'current_password' => $this->current_password,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'deconnecter_autres' => $this->deconnecterAutres,
        ], Api::token());

        if (! ($r['ok'] ?? false)) {
            $this->addError(in_array($r['champ'] ?? '', ['current_password', 'password'], true) ? $r['champ'] : 'current_password', $r['message'] ?? 'Une erreur est survenue.');

            return;
        }

        $this->reset('current_password', 'password', 'password_confirmation');
        $n = (int) ($r['deconnectes'] ?? 0);
        $this->toast('Mot de passe mis à jour.'.($n ? " {$n} autre(s) appareil(s) déconnecté(s)." : '').' Un e-mail de confirmation vous a été envoyé.');
    }

    public function demanderEmail(): void
    {
        $this->validate(['nouvelEmail' => 'required|email', 'motDePasseEmail' => 'required'], [
            'nouvelEmail.required' => 'Saisissez la nouvelle adresse.', 'nouvelEmail.email' => 'Saisissez une adresse e-mail valide.',
            'motDePasseEmail.required' => 'Confirmez avec votre mot de passe.',
        ]);
        $r = Api::post('/auth/email', ['email' => $this->nouvelEmail, 'password' => $this->motDePasseEmail], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->addError(str_contains((string) ($r['message'] ?? ''), 'passe') ? 'motDePasseEmail' : 'nouvelEmail', $r['message'] ?? 'Demande impossible.');

            return;
        }
        $this->reset('nouvelEmail', 'motDePasseEmail');
        $this->rafraichirUtilisateur();
        $this->toast($r['message'], 'info');
    }

    public function annulerEmail(): void
    {
        Api::delete('/auth/email', Api::token());
        $this->rafraichirUtilisateur();
        $this->toast('Changement d\'adresse annulé.', 'info');
    }

    public function deconnecterAppareil(int $id): void
    {
        $r = Api::delete("/auth/appareils/{$id}", Api::token());
        $this->toast($r['message'] ?? 'Appareil introuvable.', ($r['ok'] ?? false) ? 'succes' : 'erreur');
    }

    public function deconnecterTousLesAutres(): void
    {
        $r = Api::post('/auth/appareils/deconnecter-autres', [], Api::token());
        $this->toast($r['message'] ?? 'Opération impossible.', ($r['ok'] ?? false) ? 'succes' : 'erreur');
    }

    // ── Clôture ─────────────────────────────────────────────────────────

    public function cloturer(): void
    {
        $this->validate(['motDePasseCloture' => 'required', 'motifCloture' => 'nullable|string|max:500'], [
            'motDePasseCloture.required' => 'Confirmez avec votre mot de passe.',
        ]);
        $moi = Api::user();
        $r = Api::post('/auth/cloture', ['password' => $this->motDePasseCloture, 'motif' => trim($this->motifCloture) ?: null], Api::token());
        if (! ($r['ok'] ?? false)) {
            $this->addError('motDePasseCloture', $r['message'] ?? 'Clôture impossible.');

            return;
        }
        // Coffre-fort et photo effacés tout de suite ; le reste à l'échéance.
        Coffre::vider((int) $moi->id);
        $this->supprimerFichierPhoto($moi->photo ?? null);
        $date = \Carbon\Carbon::parse($r['suppression_le'])->translatedFormat('j F Y');
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('rj_toast', ['message' => "Votre compte est clôturé. Il sera supprimé le {$date} : reconnectez-vous d'ici là pour annuler.", 'type' => 'info']);
        $this->redirect('/', navigate: false);
    }

    public function render()
    {
        $user = Api::user();
        $token = Api::token();
        $data = [
            'profiles' => MembershipContent::profiles(),
            'completion' => ProfileCompletion::percent($this->completionFields()),
            'champsManquants' => ProfileCompletion::missing($this->completionFields()),
            'pagePublique' => ($code = $user->code ?? null) ? url('/carte/'.$code) : null,
            'emailEnAttente' => $user->email_nouveau ?? null,
            'estAdmin' => ($user->role ?? null) === 'admin',
        ];

        if ($this->onglet === 'profil') {
            $groupes = collect(Api::get('/groups', [], $token)['groups'] ?? [])->pluck('name')->filter()->values();
            $data['secteurs'] = $this->secteur !== '' && ! $groupes->contains($this->secteur) ? $groupes->push($this->secteur) : $groupes;
        }
        if ($this->onglet === 'notifications') {
            $n = Api::get('/auth/notifications', [], $token);
            $data['categories'] = $n['categories'] ?? [];
            $data['frequences'] = $n['frequences'] ?? [];
            $data['appareilsPush'] = (int) ($n['appareils_push'] ?? 0);
            $data['clePush'] = Api::get('/push/cle')['cle'] ?? null;
        }
        if ($this->onglet === 'securite') {
            $data['appareils'] = Api::get('/auth/appareils', [], $token)['appareils'] ?? [];
        }
        if ($this->onglet === 'compte') {
            $data['journal'] = Api::get('/auth/journal', [], $token)['evenements'] ?? [];
        }

        return view('livewire.member.profile-editor', $data);
    }
}
