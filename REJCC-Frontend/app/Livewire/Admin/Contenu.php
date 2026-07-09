<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Contenu extends Component
{
    public string $onglet = 'secteurs';

    public array $temoignagesVisibles = [true, true, false, true];

    public function setOnglet(string $onglet): void
    {
        $this->onglet = $onglet;
    }

    public function toggleTemoignage(int $index): void
    {
        $this->temoignagesVisibles[$index] = ! $this->temoignagesVisibles[$index];
    }

    protected function secteurs(): array
    {
        return [
            ['nom' => 'Entrepreneuriat', 'activites' => 12],
            ['nom' => 'Finance', 'activites' => 8],
            ['nom' => 'Technologie', 'activites' => 6],
            ['nom' => 'Agriculture', 'activites' => 5],
            ['nom' => 'Commerce', 'activites' => 9],
            ['nom' => 'Artisanat', 'activites' => 4],
        ];
    }

    protected function temoignagesData(): array
    {
        return [
            ['nom' => 'Grace Amani', 'citation' => "La formation en marketing digital m'a permis de tripler mes ventes."],
            ['nom' => 'Serge N\'Guessan', 'citation' => "Grâce au mentorat du REJCC, j'ai lancé ma première application."],
            ['nom' => 'Josiane Bamba', 'citation' => "Le réseau m'a donné la confiance pour créer ma propre agence."],
            ['nom' => 'Bertin Yao', 'citation' => "L'incubateur a transformé mon idée en coopérative viable."],
        ];
    }

    protected function partenaires(): array
    {
        return [
            ['nom' => 'Banque Atlantique CI', 'citation' => 'Partenaire financier de l\'incubateur', 'initiales' => 'BA'],
            ['nom' => 'Orange CI', 'citation' => 'Partenaire technologique', 'initiales' => 'OC'],
            ['nom' => 'Fondation Ivoire Avenir', 'citation' => 'Partenaire emploi & stages', 'initiales' => 'FI'],
            ['nom' => 'Diocèse d\'Abidjan', 'citation' => 'Partenaire institutionnel', 'initiales' => 'DA'],
        ];
    }

    protected function etapes(): array
    {
        return [
            ['numero' => '1', 'titre' => 'Remplir le formulaire de candidature', 'description' => '21 questions sur le profil, la motivation et le projet.'],
            ['numero' => '2', 'titre' => 'Entretien avec un membre du bureau', 'description' => 'Échange de 20 minutes, en ligne ou en présentiel.'],
            ['numero' => '3', 'titre' => 'Validation par le comité d\'adhésion', 'description' => 'Décision communiquée sous 5 jours ouvrés.'],
            ['numero' => '4', 'titre' => 'Intégration et remise de la carte de membre', 'description' => 'Accès à l\'espace membre et à la première session d\'accueil.'],
        ];
    }

    public function render()
    {
        $temoignages = array_map(function ($t, $i) {
            $visible = $this->temoignagesVisibles[$i];
            $t['index'] = $i;
            $t['visible'] = $visible;

            return $t;
        }, $this->temoignagesData(), array_keys($this->temoignagesData()));

        return view('livewire.admin.contenu', [
            'secteurs' => $this->secteurs(),
            'temoignages' => $temoignages,
            'partenaires' => $this->partenaires(),
            'etapes' => $this->etapes(),
        ]);
    }
}
