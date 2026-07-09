<?php

namespace App\Livewire\Member;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class Mentorat extends Component
{
    protected function mentors(): array
    {
        return [
            ['nom' => 'Pasteur Jonas Ekomi', 'specialite' => 'Leadership & vie spirituelle', 'bio' => 'Accompagne les jeunes leaders depuis 12 ans dans la conduite d\'équipes et la prise de décision.', 'note' => 4.9, 'mentores' => 14, 'online' => true],
            ['nom' => 'Grace Nkolo', 'specialite' => 'Entrepreneuriat social', 'bio' => 'Fondatrice de deux entreprises sociales, aide les porteurs de projet à structurer leur modèle économique.', 'note' => 4.8, 'mentores' => 9, 'online' => false],
            ['nom' => 'Samuel Biya', 'specialite' => 'Finance & gestion', 'bio' => 'Expert-comptable, spécialisé dans l\'accompagnement financier des jeunes entrepreneurs.', 'note' => 4.7, 'mentores' => 11, 'online' => true],
        ];
    }

    protected function historique(): array
    {
        return [
            ['jour' => '28', 'mois' => 'Juin', 'mentor' => 'Pasteur Jonas Ekomi', 'sujet' => 'Gestion des conflits en équipe', 'prochaineEtape' => 'Préparer un plan d\'action pour la prochaine réunion d\'équipe'],
            ['jour' => '14', 'mois' => 'Juin', 'mentor' => 'Samuel Biya', 'sujet' => 'Lecture du bilan prévisionnel', 'prochaineEtape' => 'Envoyer les chiffres actualisés du 2e trimestre'],
            ['jour' => '30', 'mois' => 'Mai', 'mentor' => 'Pasteur Jonas Ekomi', 'sujet' => 'Discernement et vocation', 'prochaineEtape' => 'Relire Néhémie 2 avant la prochaine session'],
        ];
    }

    public function render()
    {
        return view('livewire.member.mentorat', [
            'mentors' => $this->mentors(),
            'historique' => $this->historique(),
        ]);
    }
}
