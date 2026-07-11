<?php

namespace App\Support;

/** Couleur d'étiquette associée au statut d'un projet. */
class ProjectStatus
{
    private const COLORS = [
        'En évaluation' => '#4F6FBF',
        'En développement' => '#4F6FBF',
        'Recherche partenaires' => '#F5A623',
        'Financement en cours' => '#F5A623',
        'Lancé' => '#22A85A',
        'Financé' => '#22A85A',
        'Refusé' => '#9AA6B8',
    ];

    /** Statuts proposés dans les formulaires admin. */
    public const OPTIONS = [
        'En évaluation', 'En développement', 'Recherche partenaires',
        'Financement en cours', 'Lancé', 'Financé', 'Refusé',
    ];

    public static function color(string $status): string
    {
        return self::COLORS[$status] ?? '#4F6FBF';
    }
}
