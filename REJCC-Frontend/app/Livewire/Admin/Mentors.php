<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Mentors extends Component
{
    public array $assignations = [null, null, 'Marc Kouassi', null];

    public function assigner(int $index, string $nom): void
    {
        $this->assignations[$index] = $nom;
    }

    protected function demandesData(): array
    {
        return [
            ['membre' => 'Bertin Yao', 'domaine' => 'Levée de fonds', 'suggestions' => ['Awa Diabaté', 'Marc Kouassi']],
            ['membre' => 'Fatou Cissé', 'domaine' => 'Leadership chrétien', 'suggestions' => ['P. Emmanuel Koffi']],
            ['membre' => 'Yves Kacou', 'domaine' => 'Finance', 'suggestions' => ['Marc Kouassi']],
            ['membre' => 'Grace Amani', 'domaine' => 'Marketing digital', 'suggestions' => ['Josiane Bamba', 'Awa Diabaté']],
        ];
    }

    protected function mentors(): array
    {
        return [
            ['nom' => 'Awa Diabaté', 'specialite' => 'Levée de fonds · Stratégie', 'initiales' => 'AD', 'from' => '#031D59', 'to' => '#4F6FBF', 'online' => true, 'mentores' => 34, 'note' => '4.9'],
            ['nom' => 'P. Emmanuel Koffi', 'specialite' => 'Leadership chrétien', 'initiales' => 'EK', 'from' => '#AC0100', 'to' => '#D95B5A', 'online' => true, 'mentores' => 51, 'note' => '5.0'],
            ['nom' => 'Marc Kouassi', 'specialite' => 'Finance · Comptabilité', 'initiales' => 'MK', 'from' => '#4F6FBF', 'to' => '#8FB0FF', 'online' => false, 'mentores' => 27, 'note' => '4.8'],
            ['nom' => 'Josiane Bamba', 'specialite' => 'Communication · Marketing', 'initiales' => 'JB', 'from' => '#22A85A', 'to' => '#5BC98A', 'online' => true, 'mentores' => 19, 'note' => '4.7'],
            ['nom' => 'Serge N\'Guessan', 'specialite' => 'Développement Web', 'initiales' => 'SN', 'from' => '#031D59', 'to' => '#4F6FBF', 'online' => false, 'mentores' => 12, 'note' => '4.9'],
        ];
    }

    public function render()
    {
        $demandes = array_map(function ($d, $i) {
            $assignee = $this->assignations[$i];
            $d['index'] = $i;
            $d['assignee'] = $assignee;
            $d['montrerSelect'] = ! $assignee;

            return $d;
        }, $this->demandesData(), array_keys($this->demandesData()));

        return view('livewire.admin.mentors', [
            'demandes' => $demandes,
            'mentors' => $this->mentors(),
        ]);
    }
}
