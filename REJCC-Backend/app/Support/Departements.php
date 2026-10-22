<?php

namespace App\Support;

/**
 * Les départements du Bureau exécutif du REJCC : les 11 de l'organigramme
 * officiel (la Présidence n'en est pas un), plus la Formation, pôle du
 * département Projets doté de son propre espace. Chaque département a son espace dans le
 * dashboard et, par défaut, accès aux rubriques d'administration liées à
 * ses missions. L'administrateur général peut ajuster les rubriques de
 * chaque compte.
 */
class Departements
{
    /**
     * @var array<string, array{label: string, mission: string, sections: list<string>}>
     */
    public const LISTE = [
        'tresorerie' => [
            'label' => 'Trésorerie',
            'mission' => 'Gestion financière et comptable, perception des recettes, paiements ordonnancés.',
            'sections' => ['membres'],
        ],
        'juridique' => [
            'label' => 'Juridique',
            'mission' => 'Statuts, règlement intérieur, conventions de partenariat et conformité du réseau.',
            'sections' => ['contenu', 'documents', 'partenariats'],
        ],
        'comptable' => [
            'label' => 'Comptable',
            'mission' => 'Tenue des comptes du REJCC, suivi et recouvrement des cotisations.',
            'sections' => ['membres'],
        ],
        'informatique' => [
            'label' => 'Informatique',
            'mission' => 'Outils informatiques, applications du REJCC, sécurité et protection des données des membres.',
            'sections' => [
                'membres', 'formations', 'evenements', 'projets', 'communaute', 'certificats',
                'opportunites', 'mentors', 'groupes', 'actualites', 'contenu', 'documents',
                'contacts', 'messagerie', 'partenariats', 'audit', 'suggestions',
            ],
        ],
        'secretariat' => [
            'label' => 'Secrétariat',
            'mission' => 'Courriers administratifs, registres et archives, réunions du Bureau.',
            'sections' => ['membres', 'documents', 'contacts', 'messagerie'],
        ],
        'communication' => [
            'label' => 'Communication et Marketing',
            'mission' => 'Visibilité du REJCC dans les médias, les écoles et les entreprises, réseaux sociaux et supports visuels.',
            'sections' => ['actualites', 'evenements', 'contenu'],
        ],
        'projets' => [
            'label' => 'Projets',
            'mission' => 'Étude, validation et suivi des projets des membres, business plans, concours et financements.',
            'sections' => ['projets', 'opportunites'],
        ],
        'formation' => [
            'label' => 'Formation',
            'mission' => 'Sessions de formation des membres (création d\'entreprise, leadership, développement personnel) : formations en ligne, sessions live en visio et certificats.',
            'sections' => ['formations', 'certificats'],
        ],
        'relations-exterieures' => [
            'label' => 'Relations Extérieures',
            'mission' => 'Partenariats, opportunités d\'emploi, mise en relation des membres et référents diocésains.',
            'sections' => ['partenariats', 'opportunites', 'groupes', 'contacts'],
        ],
        'spiritualite' => [
            'label' => 'Spiritualité',
            'mission' => 'Direction spirituelle, temps de prière et de rencontre des jeunes du réseau.',
            'sections' => ['evenements'],
        ],
        'organisation' => [
            'label' => 'Organisation',
            'mission' => 'Logistique et organisation pratique des événements du REJCC.',
            'sections' => ['evenements'],
        ],
        'mentorat' => [
            'label' => 'Mentorat',
            'mission' => 'Comité des Sages et programme de mentorat des jeunes.',
            'sections' => ['mentors'],
        ],
    ];

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::LISTE);
    }

    public static function label(?string $slug): ?string
    {
        return $slug === null ? null : (self::LISTE[$slug]['label'] ?? null);
    }

    /** @return list<string> rubriques ouvertes par défaut au département */
    public static function sections(string $slug): array
    {
        return self::LISTE[$slug]['sections'] ?? [];
    }
}
