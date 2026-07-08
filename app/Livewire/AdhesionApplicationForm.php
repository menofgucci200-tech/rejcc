<?php

namespace App\Livewire;

use App\Models\MembershipApplication;
use App\Support\Content\AdhesionApplicationOptions;
use Livewire\Component;

class AdhesionApplicationForm extends Component
{
    public string $status = 'idle';

    public string $step = 'general';

    /** @var string[] */
    public array $history = [];

    // Informations générales
    public string $nom_prenoms = '';

    public string $sexe = '';

    public string $tranche_age = '';

    public string $whatsapp = '';

    public string $email = '';

    public string $connotation_religieuse = '';

    public string $paroisse = '';

    // Profil
    public array $statut_actuel = [];

    public string $niveau_etudes = '';

    public string $domaines_formation = '';

    // Compétences
    public array $competences = [];

    public string $description_competences = '';

    // Entrepreneuriat
    public string $a_activite = '';

    public string $nom_activite = '';

    public array $secteurs_activite = [];

    public string $anciennete = '';

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
        $this->validate($this->rulesFor($this->step));

        $this->history[] = $this->step;
        $this->step = $this->nextStep();
    }

    public function back(): void
    {
        if ($this->history !== []) {
            $this->step = array_pop($this->history);
        }
    }

    public function submit(): void
    {
        $this->validate($this->rulesFor('attentes'));

        MembershipApplication::create([
            'nom_prenoms' => $this->nom_prenoms,
            'sexe' => $this->sexe,
            'tranche_age' => $this->tranche_age,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'connotation_religieuse' => $this->connotation_religieuse,
            'paroisse' => $this->connotation_religieuse === 'Catholique' ? $this->paroisse : null,
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

        $this->status = 'success';
    }

    protected function rulesFor(string $step): array
    {
        return match ($step) {
            'general' => [
                'nom_prenoms' => 'required|string|min:2|max:150',
                'sexe' => 'required|in:Homme,Femme',
                'tranche_age' => 'required|string',
                'whatsapp' => ['required', 'string', 'min:8', 'max:20'],
                'email' => 'required|email|max:150',
                'connotation_religieuse' => 'required|string',
            ],
            'paroisse' => [
                'paroisse' => 'required|string',
            ],
            'profil' => [
                'statut_actuel' => 'required|array|min:1',
                'niveau_etudes' => 'required|string',
                'domaines_formation' => 'required|string|max:200',
            ],
            'competences' => [
                'competences' => 'required|array|min:1',
                'description_competences' => 'nullable|string|max:1000',
            ],
            'entrepreneuriat' => [
                'a_activite' => 'required|in:Oui,Non',
            ],
            'activite_actuelle' => [
                'nom_activite' => 'nullable|string|max:150',
                'secteurs_activite' => 'required|array|min:1',
                'anciennete' => 'required|string',
            ],
            'activite_future' => [
                'domaines_futurs' => 'required|array|min:1',
            ],
            'attentes' => [
                'attentes' => 'required|array|min:1',
                'formations_interet' => 'required|array|min:1',
                'defi_principal' => 'required|string',
                'revenu_mensuel' => 'required|string',
            ],
            default => [],
        };
    }

    protected function nextStep(): string
    {
        return match ($this->step) {
            'general' => $this->connotation_religieuse === 'Catholique' ? 'paroisse' : 'profil',
            'paroisse' => 'profil',
            'profil' => 'competences',
            'competences' => 'entrepreneuriat',
            'entrepreneuriat' => $this->a_activite === 'Oui' ? 'activite_actuelle' : 'activite_future',
            'activite_actuelle', 'activite_future' => 'attentes',
            default => 'attentes',
        };
    }

    public function render()
    {
        return view('livewire.adhesion-application-form', [
            'sexes' => AdhesionApplicationOptions::sexes(),
            'tranchesAge' => AdhesionApplicationOptions::tranchesAge(),
            'connotationsReligieuses' => AdhesionApplicationOptions::connotationsReligieuses(),
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
        ]);
    }
}
