<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Emplois extends Component
{
    public array $statuts = ['attente', 'attente', 'approuve', 'approuve', 'approuve', 'approuve'];

    public function approuver(int $index): void
    {
        $this->statuts[$index] = 'approuve';
    }

    public function rejeter(int $index): void
    {
        $this->statuts[$index] = 'rejete';
    }

    protected function data(): array
    {
        return [
            ['poste' => 'Chargé(e) de projet junior', 'entreprise' => 'AgriConnect CI', 'lieu' => 'Abidjan', 'type' => 'Emploi'],
            ['poste' => 'Stagiaire communication digitale', 'entreprise' => 'Boutique solidaire', 'lieu' => 'Abidjan', 'type' => 'Stage'],
            ['poste' => 'Développeur mobile (Flutter)', 'entreprise' => 'FinTech Partenaire', 'lieu' => 'Remote', 'type' => 'Emploi'],
            ['poste' => 'Analyste financier junior', 'entreprise' => 'Cabinet KM Conseils', 'lieu' => 'Abidjan', 'type' => 'Emploi'],
            ['poste' => 'Stagiaire gestion de projet', 'entreprise' => 'REJCC Incubateur', 'lieu' => 'Abidjan', 'type' => 'Stage'],
            ['poste' => 'Chargé(e) de partenariats', 'entreprise' => 'Fondation Ivoire Avenir', 'lieu' => 'Yamoussoukro', 'type' => 'Emploi'],
        ];
    }

    public function render()
    {
        $offres = array_map(function ($o, $i) {
            $s = $this->statuts[$i];
            $info = $s === 'approuve' ? ['#22A85A', '#EAF6EE', 'Approuvée'] : ($s === 'rejete' ? ['#AC0100', '#F9E9E9', 'Rejetée'] : ['#F5A623', '#FCF1DD', 'En attente']);
            $o['index'] = $i;
            $o['initiales'] = mb_strtoupper(collect(explode(' ', $o['entreprise']))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''));
            $o['typeColor'] = $o['type'] === 'Emploi' ? '#031D59' : '#AC0100';
            $o['typeBg'] = $o['type'] === 'Emploi' ? '#E8EDF8' : '#F9E9E9';
            $o['statutColor'] = $info[0];
            $o['statutBg'] = $info[1];
            $o['statutLabel'] = $info[2];
            $o['enAttente'] = $s === 'attente';

            return $o;
        }, $this->data(), array_keys($this->data()));

        return view('livewire.admin.emplois', ['offres' => $offres]);
    }
}
