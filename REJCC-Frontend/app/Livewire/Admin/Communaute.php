<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Communaute extends Component
{
    public array $statuts = ['attente', 'attente', 'attente', 'attente'];

    public function masquer(int $index): void
    {
        $this->statuts[$index] = 'masque';
    }

    public function ignorer(int $index): void
    {
        $this->statuts[$index] = 'ignore';
    }

    protected function signalementsData(): array
    {
        return [
            ['titre' => 'Commentaire sur "Comment structurer sa première levée"', 'motif' => 'Contenu commercial non sollicité', 'auteur' => 'un membre'],
            ['titre' => 'Publication dans "Groupe de prière des entrepreneurs"', 'motif' => 'Hors sujet', 'auteur' => 'un membre'],
            ['titre' => 'Message privé signalé', 'motif' => 'Comportement inapproprié', 'auteur' => 'Grace Amani'],
            ['titre' => 'Commentaire sur le projet AgriConnect CI', 'motif' => 'Propos déplacés', 'auteur' => 'Serge N\'Guessan'],
        ];
    }

    protected function discussions(): array
    {
        return [
            ['titre' => '💬 Comment structurer sa première levée ?', 'auteur' => 'Awa D.', 'reponses' => 18],
            ['titre' => '🚀 Projet collaboratif : AgriConnect CI', 'auteur' => 'Marc K.', 'reponses' => 31],
            ['titre' => '🙏 Groupe de prière des entrepreneurs', 'auteur' => 'P. Emmanuel', 'reponses' => 9],
            ['titre' => '📈 Vos outils préférés pour le suivi de trésorerie', 'auteur' => 'Josiane B.', 'reponses' => 14],
            ['titre' => '🤝 Qui est partant pour un stand au prochain salon ?', 'auteur' => 'Serge N.', 'reponses' => 22],
        ];
    }

    public function render()
    {
        $signalements = array_map(function ($s, $i) {
            $st = $this->statuts[$i];
            $info = $st === 'masque' ? ['#AC0100', '#F9E9E9', 'Masqué'] : ($st === 'ignore' ? ['#22A85A', '#EAF6EE', 'Ignoré'] : ['#F5A623', '#FCF1DD', 'En attente']);
            $s['index'] = $i;
            $s['statutColor'] = $info[0];
            $s['statutBg'] = $info[1];
            $s['statutLabel'] = $info[2];
            $s['enAttente'] = $st === 'attente';

            return $s;
        }, $this->signalementsData(), array_keys($this->signalementsData()));

        return view('livewire.admin.communaute', [
            'signalements' => $signalements,
            'discussions' => $this->discussions(),
        ]);
    }
}
