<?php

namespace App\Support\Content;

class SiteConfig
{
    public static function get(): array
    {
        return [
            'name' => 'REJCC',
            'fullName' => "Réseau Entrepreneurial des Jeunes Catholiques de Côte d'Ivoire",
            'slogan' => "Ensemble pour l'excellence.",
            'promise' => 'Accéder à un réseau de qualité.',
            'positioning' => "Le réseau de référence des jeunes entrepreneurs et porteurs de projets catholiques, alliant foi, innovation et entrepreneuriat pour bâtir des entreprises à impact durable.",
            'mission' => "Connecter les jeunes entrepreneurs et porteurs de projets catholiques pour favoriser la co-création, le partage de ressources et le développement de solutions technologiques et commerciales durables.",
            'vision' => "Devenir le premier incubateur de talents et d'entreprises au service de l'Église et de la société en Côte d'Ivoire, en bâtissant une communauté solidaire, innovante et économiquement autonome.",
            'about' => "Le REJCC est une communauté de jeunes entrepreneurs et porteurs de projets catholiques unis par la volonté de collaborer, d'innover et de bâtir des solutions durables au service du développement.",
            'url' => 'https://rejcc.site',
            'contact' => [
                'city' => 'Abidjan',
                'country' => "Côte d'Ivoire",
                'address' => "Abidjan, Côte d'Ivoire",
                'email' => 'contact@rejcc.site',
                'phone' => '+225 00 00 00 00',
            ],
        ];
    }

    public static function nav(): array
    {
        return [
            ['label' => 'Accueil', 'href' => '/'],
            ['label' => 'À propos', 'href' => '/a-propos'],
            ['label' => 'Activités', 'href' => '/activites'],
            ['label' => 'Domaines', 'href' => '/domaines'],
            ['label' => 'Événements', 'href' => '/evenements'],
            ['label' => 'Actualités', 'href' => '/actualites'],
            ['label' => 'Partenaires', 'href' => '/partenaires'],
            ['label' => 'Contact', 'href' => '/contact'],
        ];
    }

    public static function ctaPrimary(): array
    {
        return ['label' => 'Adhérer', 'href' => '/adhesion'];
    }

    public static function socials(): array
    {
        return [
            ['label' => 'Facebook', 'href' => '#', 'icon' => 'facebook'],
            ['label' => 'Instagram', 'href' => '#', 'icon' => 'instagram'],
            ['label' => 'LinkedIn', 'href' => '#', 'icon' => 'linkedin'],
            ['label' => 'YouTube', 'href' => '#', 'icon' => 'youtube'],
        ];
    }
}
