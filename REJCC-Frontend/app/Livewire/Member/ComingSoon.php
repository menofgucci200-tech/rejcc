<?php

namespace App\Livewire\Member;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.member-light')]
class ComingSoon extends Component
{
    public string $page;

    public function mount(string $page): void
    {
        $this->page = $page;
    }

    protected function meta(): array
    {
        return [
            'formations' => ['title' => 'Mes formations', 'icon' => 'graduation-cap', 'description' => "Suivez ici la progression de vos formations en cours et l'historique de celles déjà terminées."],
            'catalogue' => ['title' => 'Catalogue', 'icon' => 'nav-compass', 'description' => "Parcourez l'ensemble des formations proposées par le réseau et inscrivez-vous à celles qui vous intéressent."],
            'parcours' => ['title' => 'Mes parcours', 'icon' => 'nav-route', 'description' => 'Suivez des parcours de formation structurés, pensés pour vous accompagner étape par étape.'],
            'mentorat' => ['title' => 'Mentorat', 'icon' => 'nav-mentor', 'description' => 'Trouvez un mentor ou proposez votre accompagnement à un autre membre du réseau.'],
            'communaute' => ['title' => 'Communauté', 'icon' => 'message-circle', 'description' => 'Échangez avec les autres membres du réseau autour de sujets qui vous rassemblent.'],
            'evenements' => ['title' => 'Événements', 'icon' => 'calendar-days', 'description' => 'Retrouvez ici tous les événements du réseau et inscrivez-vous en un clic.'],
            'projets' => ['title' => 'Projets', 'icon' => 'nav-projects', 'description' => 'Présentez vos projets entrepreneuriaux et découvrez ceux des autres membres.'],
            'incubateur' => ['title' => 'Incubateur', 'icon' => 'nav-incubator', 'description' => "Accédez à l'accompagnement de l'incubateur REJCC pour faire grandir votre projet."],
            'emplois' => ['title' => 'Opportunités', 'icon' => 'nav-briefcase', 'description' => "Consultez les offres d'emploi et opportunités partagées par le réseau."],
            'ressources' => ['title' => 'Ressources', 'icon' => 'nav-library', 'description' => 'Documents, guides et supports mis à disposition par le réseau.'],
            'certificats' => ['title' => 'Certificats', 'icon' => 'award', 'description' => 'Retrouvez ici les certificats obtenus à l\'issue de vos formations.'],
        ];
    }

    public function render()
    {
        $meta = $this->meta()[$this->page] ?? ['title' => 'Bientôt disponible', 'icon' => 'info', 'description' => ''];

        return view('livewire.member.coming-soon', [
            'title' => $meta['title'],
            'icon' => $meta['icon'],
            'description' => $meta['description'],
        ]);
    }
}
