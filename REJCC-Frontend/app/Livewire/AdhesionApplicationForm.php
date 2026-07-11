<?php

namespace App\Livewire;

use App\Support\Api;
use App\Support\Content\AdhesionApplicationOptions;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.wizard')]
class AdhesionApplicationForm extends Component
{
    public string $status = 'idle';

    public int $step = 0;

    /** @var string[] */
    public array $stepTitles = [
        'Informations générales', 'Diocèse & paroisse', 'Profil', 'Compétences',
        'Entrepreneuriat', 'Projet futur', 'Attentes', 'Récapitulatif',
    ];

    // Informations générales
    public string $nom_complet = '';

    public string $prenom = '';

    public string $nom = '';

    public string $sexe = '';

    public string $tranche_age = '';

    public string $whatsapp = '';

    public string $email = '';

    public string $ville = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Diocèse & paroisse
    public string $diocese = '';

    public string $paroisse = '';

    // Profil
    public array $statut_actuel = [];

    public string $niveau_etudes = '';

    // Compétences
    public string $domaines_formation = '';

    public array $competences = [];

    public string $description_competences = '';

    // Entrepreneuriat
    public string $a_activite = '';

    public string $nom_activite = '';

    public array $secteurs_activite = [];

    public string $anciennete = '';

    // Projet futur
    public array $domaines_futurs = [];

    // Attentes et profil
    public array $attentes = [];

    public array $formations_interet = [];

    public string $defi_principal = '';

    public string $revenu_mensuel = '';

    public function select(string $field, string $value): void
    {
        $this->{$field} = $value;
    }

    public function toggle(string $field, string $value): void
    {
        $current = $this->{$field};

        $this->{$field} = in_array($value, $current, true)
            ? array_values(array_diff($current, [$value]))
            : [...$current, $value];
    }

    public function next(): void
    {
        if ($this->step === 7) {
            $this->submit();

            return;
        }

        $this->validate($this->rulesFor($this->step));

        // « Nom et prénoms » saisi en un seul champ : on le scinde (premier mot
        // = prénom, le reste = nom) pour alimenter l'API qui les attend séparés.
        if ($this->step === 0 && ! $this->splitNomComplet()) {
            return;
        }

        $this->step = min(7, $this->step + 1);
    }

    private function splitNomComplet(): bool
    {
        $parts = preg_split('/\s+/', trim($this->nom_complet), -1, PREG_SPLIT_NO_EMPTY);
        $prenom = $parts[0] ?? '';
        $nom = implode(' ', array_slice($parts, 1));

        if (count($parts) < 2 || mb_strlen($prenom) < 2 || mb_strlen($nom) < 2) {
            $this->addError('nom_complet', 'Saisissez votre nom et vos prénoms (au moins deux mots).');

            return false;
        }

        $this->prenom = $prenom;
        $this->nom = $nom;

        return true;
    }

    public function back(): void
    {
        $this->step = max(0, $this->step - 1);
    }

    protected function submit(): void
    {
        // Filet de sécurité si l'on arrive à la soumission sans re-passer l'étape 0.
        if ($this->prenom === '' && ! $this->splitNomComplet()) {
            $this->step = 0;

            return;
        }

        $result = Api::post('/membership-applications', [
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'sexe' => $this->sexe,
            'tranche_age' => $this->tranche_age,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'diocese' => $this->diocese,
            'ville' => $this->ville,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'paroisse' => $this->paroisse,
            'statut_actuel' => $this->statut_actuel,
            'niveau_etudes' => $this->niveau_etudes,
            'domaines_formation' => $this->domaines_formation,
            'competences' => $this->competences,
            'description_competences' => $this->description_competences ?: null,
            'a_activite' => $this->a_activite,
            'nom_activite' => $this->a_activite === 'Oui' ? ($this->nom_activite ?: null) : null,
            'secteurs_activite' => $this->a_activite === 'Oui' ? $this->secteurs_activite : null,
            'anciennete' => $this->a_activite === 'Oui' ? $this->anciennete : null,
            'domaines_futurs' => $this->a_activite === 'Non' ? $this->domaines_futurs : null,
            'attentes' => $this->attentes,
            'formations_interet' => $this->formations_interet,
            'defi_principal' => $this->defi_principal,
            'revenu_mensuel' => $this->revenu_mensuel,
        ]);

        if (! ($result['ok'] ?? false)) {
            $message = $result['message'] ?? 'Une erreur est survenue, réessayez.';

            if (str_contains($message, 'e-mail') || str_contains($message, 'mot de passe')) {
                $this->step = 0;
                $this->addError('email', $message);
            } else {
                $this->addError('revenu_mensuel', $message);
            }

            return;
        }

        $this->status = 'success';
    }

    public function restart(): void
    {
        $this->reset();
    }

    protected function rulesFor(int $step): array
    {
        return match ($step) {
            0 => [
                'nom_complet' => 'required|string|min:3|max:160',
                'sexe' => 'required|in:Homme,Femme',
                'tranche_age' => 'required|string',
                'whatsapp' => ['required', 'string', 'min:8', 'max:20'],
                'email' => 'required|email|max:150',
                'ville' => 'required|string|max:80',
                'password' => 'required|string|min:8|confirmed',
            ],
            1 => [
                'diocese' => 'required|string',
                'paroisse' => 'required|string',
            ],
            2 => [
                'statut_actuel' => 'required|array|min:1',
                'niveau_etudes' => 'required|string',
            ],
            3 => [
                'domaines_formation' => 'required|string|max:200',
                'competences' => 'required|array|min:1',
                'description_competences' => 'nullable|string|max:1000',
            ],
            4 => array_merge(
                ['a_activite' => 'required|in:Oui,Non'],
                $this->a_activite === 'Oui' ? [
                    'secteurs_activite' => 'required|array|min:1',
                    'anciennete' => 'required|string',
                ] : []
            ),
            5 => $this->a_activite === 'Non' ? ['domaines_futurs' => 'required|array|min:1'] : [],
            6 => [
                'attentes' => 'required|array|min:1',
                'formations_interet' => 'required|array|min:1',
                'defi_principal' => 'required|string',
                'revenu_mensuel' => 'required|string',
            ],
            default => [],
        };
    }

    public function render()
    {
        $recap = [
            ['label' => 'Nom et prénoms', 'value' => trim($this->nom_complet) ?: '—'],
            ['label' => 'Contact', 'value' => collect([$this->whatsapp, $this->email])->filter()->join(' · ') ?: '—'],
            ['label' => 'Ville', 'value' => $this->ville ?: '—'],
            ['label' => 'Diocèse / Paroisse', 'value' => collect([$this->diocese, $this->paroisse])->filter()->join(' — ') ?: '—'],
            ['label' => 'Statut', 'value' => implode(', ', $this->statut_actuel) ?: '—'],
            ['label' => 'Compétences', 'value' => implode(', ', $this->competences) ?: '—'],
            ['label' => 'Activité actuelle', 'value' => $this->a_activite ?: '—'],
            ['label' => 'Attentes principales', 'value' => implode(', ', $this->attentes) ?: '—'],
            ['label' => 'Principal défi', 'value' => $this->defi_principal ?: '—'],
        ];

        return view('livewire.adhesion-application-form', [
            'sexes' => AdhesionApplicationOptions::sexes(),
            'tranchesAge' => AdhesionApplicationOptions::tranchesAge(),
            'dioceses' => AdhesionApplicationOptions::dioceses(),
            'paroisses' => AdhesionApplicationOptions::paroisses(),
            'statutsActuels' => AdhesionApplicationOptions::statutsActuels(),
            'niveauxEtudes' => AdhesionApplicationOptions::niveauxEtudes(),
            'competencesOptions' => AdhesionApplicationOptions::competences(),
            'secteursActivite' => AdhesionApplicationOptions::secteursActivite(),
            'anciennetes' => AdhesionApplicationOptions::anciennetes(),
            'attentesOptions' => AdhesionApplicationOptions::attentes(),
            'formationsInteretOptions' => AdhesionApplicationOptions::formationsInteret(),
            'defis' => AdhesionApplicationOptions::defis(),
            'revenus' => AdhesionApplicationOptions::revenus(),
            'recap' => $recap,
        ]);
    }
}
