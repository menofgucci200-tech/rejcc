<?php

namespace App\Support\Content;

/**
 * Registre des pages vitrine éditables depuis l'admin (Pages du site) :
 * pour chaque page, ses sections, leurs champs modifiables et leurs valeurs
 * par défaut (celles codées dans les vues). Une section sans champs n'offre
 * que le bouton afficher/masquer. `locked` = section non masquable.
 */
class PageCatalog
{
    public static function pages(): array
    {
        return [
            'home' => [
                'label' => 'Accueil',
                'path' => '/',
                'sections' => [
                    'hero' => [
                        'label' => 'Bandeau principal (héro)',
                        'locked' => true,
                        'fields' => [
                            'eyebrow' => ['label' => 'Sur-titre', 'type' => 'text', 'default' => "Réseau entrepreneurial catholique · Côte d'Ivoire"],
                            'subtitle' => ['label' => 'Texte d\'introduction', 'type' => 'textarea', 'default' => "Le réseau de référence des jeunes entrepreneurs et porteurs de projets catholiques. Collaborer, innover et bâtir des entreprises à impact durable — au service de l'Église et de la société."],
                            'note' => ['label' => 'Note sous les boutons', 'type' => 'text', 'default' => '350+ membres déjà engagés dans 33 domaines.'],
                        ],
                    ],
                    'about' => [
                        'label' => 'Qui sommes-nous',
                        'hint' => 'Les textes (mission, vision, à propos) se modifient dans Réglages du site → Identité.',
                        'fields' => [],
                    ],
                    'stats' => [
                        'label' => 'Chiffres clés',
                        'hint' => 'Les valeurs se modifient dans Contenu du site → Chiffres clés.',
                        'fields' => [],
                    ],
                    'why-join' => [
                        'label' => 'Pourquoi nous rejoindre',
                        'fields' => [
                            'subtitle' => ['label' => 'Texte d\'introduction', 'type' => 'textarea', 'default' => 'Le REJCC met à votre disposition un environnement complet pour faire grandir vos projets et vos compétences.'],
                        ],
                    ],
                    'values' => ['label' => 'Nos valeurs', 'fields' => []],
                    'domains-preview' => [
                        'label' => 'Aperçu des domaines',
                        'hint' => 'Les secteurs se modifient dans Contenu du site → Secteurs.',
                        'fields' => [],
                    ],
                    'how-to-join' => [
                        'label' => 'Comment adhérer',
                        'hint' => 'Les étapes se modifient dans Contenu du site → Étapes d\'adhésion.',
                        'fields' => [],
                    ],
                    'testimonials' => [
                        'label' => 'Témoignages',
                        'hint' => 'Les témoignages se modifient dans Contenu du site → Témoignages.',
                        'fields' => [],
                    ],
                    'events' => ['label' => 'Événements à venir', 'fields' => []],
                    'news' => ['label' => 'Dernières actualités', 'fields' => []],
                    'cta-band' => [
                        'label' => 'Bandeau final (appel à l\'action)',
                        'fields' => [
                            'title' => ['label' => 'Titre', 'type' => 'text', 'default' => 'Prêt à écrire votre réussite ?'],
                            'text' => ['label' => 'Texte', 'type' => 'textarea', 'default' => 'Rejoignez une communauté de jeunes entrepreneurs catholiques déterminés à grandir, collaborer et réussir — ensemble.'],
                        ],
                    ],
                ],
            ],
            'a-propos' => [
                'label' => 'À propos',
                'path' => '/a-propos',
                'sections' => [
                    'header' => [
                        'label' => 'En-tête de page',
                        'locked' => true,
                        'fields' => [
                            'title' => ['label' => 'Titre (laisser vide pour garder le titre stylisé)', 'type' => 'text', 'default' => ''],
                            'subtitle' => ['label' => 'Sous-titre', 'type' => 'textarea', 'default' => "Le REJCC est une communauté de jeunes entrepreneurs et porteurs de projets catholiques unis par la volonté de collaborer, d'innover et de bâtir des solutions durables au service du développement."],
                        ],
                    ],
                    'histoire' => [
                        'label' => 'Notre histoire',
                        'fields' => [
                            'subtitle' => ['label' => 'Texte', 'type' => 'textarea', 'default' => "Le REJCC est né de la volonté de jeunes entrepreneurs catholiques de Côte d'Ivoire de conjuguer leur foi, leur ambition et leur sens du service pour bâtir, ensemble, une nouvelle génération d'entreprises à impact."],
                        ],
                    ],
                ],
            ],
            'activites' => [
                'label' => 'Activités',
                'path' => '/activites',
                'sections' => [
                    'header' => [
                        'label' => 'En-tête de page',
                        'locked' => true,
                        'fields' => [
                            'title' => ['label' => 'Titre (laisser vide pour garder le titre stylisé)', 'type' => 'text', 'default' => ''],
                            'subtitle' => ['label' => 'Sous-titre', 'type' => 'textarea', 'default' => "Un programme riche pour apprendre, entreprendre et grandir ensemble, tout au long de l'année."],
                        ],
                    ],
                ],
            ],
            'domaines' => [
                'label' => 'Domaines',
                'path' => '/domaines',
                'sections' => [
                    'header' => [
                        'label' => 'En-tête de page',
                        'locked' => true,
                        'fields' => [
                            'title' => ['label' => 'Titre (laisser vide pour garder le titre stylisé)', 'type' => 'text', 'default' => ''],
                            'subtitle' => ['label' => 'Sous-titre', 'type' => 'textarea', 'default' => 'Le réseau rassemble des entrepreneurs de tous les secteurs. Trouvez le vôtre et connectez-vous aux bonnes personnes.'],
                        ],
                    ],
                ],
            ],
            'partenaires' => [
                'label' => 'Partenaires',
                'path' => '/partenaires',
                'sections' => [
                    'header' => [
                        'label' => 'En-tête de page',
                        'locked' => true,
                        'fields' => [
                            'title' => ['label' => 'Titre (laisser vide pour garder le titre stylisé)', 'type' => 'text', 'default' => ''],
                            'subtitle' => ['label' => 'Sous-titre', 'type' => 'textarea', 'default' => "Entreprises, institutions et organisations qui soutiennent l'entrepreneuriat des jeunes catholiques de Côte d'Ivoire."],
                        ],
                    ],
                ],
            ],
            'contact' => [
                'label' => 'Contact',
                'path' => '/contact',
                'sections' => [
                    'header' => [
                        'label' => 'En-tête de page',
                        'locked' => true,
                        'fields' => [
                            'title' => ['label' => 'Titre (laisser vide pour garder le titre stylisé)', 'type' => 'text', 'default' => ''],
                            'subtitle' => ['label' => 'Sous-titre', 'type' => 'textarea', 'default' => "Une question, un projet, une envie de collaborer ? L'équipe du REJCC vous répond."],
                        ],
                    ],
                ],
            ],
        ];
    }
}
