<?php

namespace App\Support\Content;

class SiteConfig
{
    public static function get(): array
    {
        return [
            'name' => 'REJCC',
            'fullName' => "Réseau Entrepreneurial des Jeunes Catholiques de Côte d'Ivoire",
            'slogan' => SiteRemote::setting('identity.slogan', "Ensemble pour l'excellence."),
            'promise' => SiteRemote::setting('identity.promise', 'Accéder à un réseau de qualité.'),
            'positioning' => SiteRemote::setting('identity.positioning', "Le réseau de référence des jeunes entrepreneurs et porteurs de projets catholiques, alliant foi, innovation et entrepreneuriat pour bâtir des entreprises à impact durable."),
            'mission' => SiteRemote::setting('identity.mission', "Connecter les jeunes entrepreneurs et porteurs de projets catholiques pour favoriser la co-création, le partage de ressources et le développement de solutions technologiques et commerciales durables."),
            'vision' => SiteRemote::setting('identity.vision', "Devenir le premier incubateur de talents et d'entreprises au service de l'Église et de la société en Côte d'Ivoire, en bâtissant une communauté solidaire, innovante et économiquement autonome."),
            'about' => SiteRemote::setting('identity.about', "Le REJCC est une communauté de jeunes entrepreneurs et porteurs de projets catholiques unis par la volonté de collaborer, d'innover et de bâtir des solutions durables au service du développement."),
            'url' => 'https://rejcc.site',
            'contact' => [
                'city' => SiteRemote::setting('contact.city', 'Abidjan'),
                'country' => "Côte d'Ivoire",
                'address' => SiteRemote::setting('contact.address', "Abidjan, Côte d'Ivoire"),
                'email' => SiteRemote::setting('contact.email', 'contact@rejcc.site'),
                'phone' => SiteRemote::setting('contact.phone', '+225 00 00 00 00'),
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

    /** Réseaux sociaux : seuls ceux dont l'admin a saisi l'URL sont affichés. */
    public static function socials(): array
    {
        $networks = [
            ['label' => 'Facebook', 'key' => 'social.facebook', 'icon' => 'facebook'],
            ['label' => 'Instagram', 'key' => 'social.instagram', 'icon' => 'instagram'],
            ['label' => 'LinkedIn', 'key' => 'social.linkedin', 'icon' => 'linkedin'],
            ['label' => 'YouTube', 'key' => 'social.youtube', 'icon' => 'youtube'],
            ['label' => 'TikTok', 'key' => 'social.tiktok', 'icon' => 'tiktok'],
            ['label' => 'WhatsApp', 'key' => 'social.whatsapp', 'icon' => 'whatsapp'],
        ];

        $links = [];
        foreach ($networks as $n) {
            $href = SiteRemote::setting($n['key'], '');
            if ($href !== '' && $href !== '#') {
                $links[] = ['label' => $n['label'], 'href' => $href, 'icon' => $n['icon']];
            }
        }

        // Aucun lien configuré : on garde les icônes historiques (href #)
        // pour ne pas vider le pied de page.
        if ($links === []) {
            return [
                ['label' => 'Facebook', 'href' => '#', 'icon' => 'facebook'],
                ['label' => 'Instagram', 'href' => '#', 'icon' => 'instagram'],
                ['label' => 'LinkedIn', 'href' => '#', 'icon' => 'linkedin'],
                ['label' => 'YouTube', 'href' => '#', 'icon' => 'youtube'],
            ];
        }

        return $links;
    }

    /** Bandeau d'annonce global (affiché en haut de la vitrine si actif). */
    public static function banner(): ?array
    {
        if (! SiteRemote::setting('banner.enabled', false)) {
            return null;
        }

        $text = trim((string) SiteRemote::setting('banner.text', ''));
        if ($text === '') {
            return null;
        }

        return [
            'text' => $text,
            'link' => SiteRemote::setting('banner.link', ''),
            'label' => SiteRemote::setting('banner.label', 'En savoir plus'),
        ];
    }
}
