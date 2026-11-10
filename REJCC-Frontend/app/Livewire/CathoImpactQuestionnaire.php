<?php

namespace App\Livewire;

use App\Support\Api;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulaire public CathoImpact, volontairement court : il sert à présélectionner
 * les parcours. Les personnes retenues reçoivent ensuite un questionnaire détaillé
 * de la Communication. La photo (facultative) reste en stockage privé, l'API
 * n'enregistre que son chemin.
 */
#[Layout('layouts.wizard', [
    'titre' => 'Formulaire CathoImpact · REJCC',
    'description' => 'Proposez votre parcours d\'entrepreneur ou de professionnel catholique au REJCC pour être mis en avant dans CathoImpact.',
])]
class CathoImpactQuestionnaire extends Component
{
    use WithFileUploads;

    #[Locked]
    public array $profils = [];

    #[Locked]
    public string $consentementTexte = '';

    #[Locked]
    public int $parcoursMax = 1500;

    #[Locked]
    public int $inspirationMax = 1000;

    public string $status = 'idle';

    /** 0 : profil ; 1 : formulaire. */
    public int $step = 0;

    public string $profil = '';

    public string $nom_complet = '';

    public string $email = '';

    public string $whatsapp = '';

    public string $entreprise = '';

    public string $fonction = '';

    public string $secteur = '';

    public string $ville = '';

    public string $parcours = '';

    public string $inspiration = '';

    public string $lien = '';

    /** Portrait facultatif (déjà compressé dans le navigateur). */
    public $photo = null;

    public bool $consentement = false;

    /** Champ invisible : seuls les robots le remplissent. */
    public string $site_web = '';

    public function mount(): void
    {
        $r = Api::get('/cathoimpact/questionnaire');
        abort_unless($r['ok'] ?? false, 503);

        $this->profils = $r['profils'];
        $this->consentementTexte = $r['consentement'];
        $this->parcoursMax = $r['parcours_max'];
        $this->inspirationMax = $r['inspiration_max'];

        // Membre connecté : identité déjà connue.
        if ($user = Api::user()) {
            $this->nom_complet = trim(($user->prenom ?? '').' '.($user->nom ?? ''));
            $this->email = (string) ($user->email ?? '');
            $this->whatsapp = (string) ($user->telephone ?? '');
            $this->ville = (string) ($user->ville ?? '');
        }
    }

    public function choisirProfil(string $profil): void
    {
        if (! isset($this->profils[$profil])) {
            return;
        }
        $this->profil = $profil;
        $this->allerA(1);
    }

    public function precedent(): void
    {
        $this->resetErrorBag();
        $this->allerA(0);
    }

    /** Change d'étape et remonte en haut : le formulaire est long sur téléphone. */
    private function allerA(int $step): void
    {
        $this->step = $step;
        $this->js('window.scrollTo(0, 0)');
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => 'image|mimes:jpg,jpeg,png,webp|max:10240'], [
            'photo.image' => 'Ce fichier n\'est pas une photo.',
            'photo.mimes' => 'Formats acceptés : JPG, PNG ou WebP.',
            'photo.max' => 'La photo dépasse 10 Mo.',
        ]);
    }

    public function retirerPhoto(): void
    {
        $this->photo = null;
        $this->resetErrorBag('photo');
    }

    public function envoyer(): void
    {
        if ($this->site_web !== '') {
            $this->status = 'success';

            return;
        }

        // Sur téléphone, le premier champ en erreur peut être loin au-dessus du bouton.
        $this->js("setTimeout(() => document.querySelector('form .border-accent')?.scrollIntoView({ block: 'center', behavior: 'smooth' }), 80)");
        $this->validate([
            'profil' => 'required|in:'.implode(',', array_keys($this->profils)),
            'nom_complet' => 'required|string|min:3|max:150',
            'email' => 'required|email|max:150',
            'whatsapp' => ['required', 'regex:/^\+?[0-9 ]{8,20}$/'],
            'entreprise' => 'required|string|min:2|max:150',
            'fonction' => 'required|string|min:2|max:150',
            'secteur' => 'required|string|min:2|max:100',
            'ville' => 'required|string|min:2|max:100',
            'parcours' => "required|string|min:20|max:{$this->parcoursMax}",
            'inspiration' => "required|string|min:10|max:{$this->inspirationMax}",
            'lien' => 'nullable|url:http,https|max:500',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'consentement' => 'accepted',
        ], [
            'nom_complet.required' => 'Indiquez votre nom complet.',
            'nom_complet.min' => 'Indiquez votre nom complet.',
            'email.required' => 'Indiquez votre adresse e-mail.',
            'email.email' => 'Cette adresse e-mail n\'est pas valide.',
            'whatsapp.required' => 'Indiquez votre numéro WhatsApp.',
            'whatsapp.regex' => 'Ce numéro n\'est pas valide.',
            'entreprise.required' => 'Ce champ est obligatoire.',
            'fonction.required' => 'Ce champ est obligatoire.',
            'secteur.required' => 'Indiquez votre secteur d\'activité.',
            'ville.required' => 'Indiquez votre ville.',
            'parcours.required' => 'Racontez votre parcours en quelques lignes.',
            'parcours.min' => 'Racontez votre parcours en quelques lignes (20 caractères au moins).',
            'parcours.max' => "Votre parcours dépasse {$this->parcoursMax} caractères.",
            'inspiration.required' => 'Dites-nous ce qui rend votre parcours inspirant.',
            'inspiration.min' => 'Développez un peu votre réponse.',
            'inspiration.max' => "Votre réponse dépasse {$this->inspirationMax} caractères.",
            'lien.url' => 'Collez un lien complet, qui commence par https://',
            'consentement.accepted' => 'Cochez la case pour autoriser la publication, sans quoi nous ne pouvons pas utiliser votre récit.',
        ]);

        $chemin = null;
        if ($this->photo) {
            $ext = strtolower($this->photo->guessExtension() ?: 'jpg');
            $chemin = $this->photo->storeAs('cathoimpact/'.now()->format('Y/m'), Str::uuid().'.'.$ext, 'local');
        }

        $r = Api::post('/cathoimpact/reponses', [
            'profil' => $this->profil,
            'nom_complet' => $this->nom_complet,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'entreprise' => $this->entreprise,
            'fonction' => $this->fonction,
            'secteur' => $this->secteur,
            'ville' => $this->ville,
            'parcours' => $this->parcours,
            'inspiration' => $this->inspiration,
            'lien' => trim($this->lien) ?: null,
            'photo' => $chemin,
            'consentement' => $this->consentement,
        ]);

        if (! ($r['ok'] ?? false)) {
            if ($chemin) {
                Storage::disk('local')->delete($chemin);
            }
            $this->addError('envoi', $r['message'] ?? 'Une erreur est survenue, réessayez.');

            return;
        }

        $this->status = 'success';
        $this->js('window.scrollTo(0, 0)');
    }

    public function render()
    {
        return view('livewire.cathoimpact-questionnaire');
    }
}
