<?php

namespace App\Support;

/** Couleurs des statuts (circuit de validation) et des stades d'un projet. */
class ProjectStatus
{
    private const STATUTS = [
        'evaluation' => '#4F6FBF',
        'a_completer' => '#B27007',
        'valide' => '#1C8F4C',
        'refuse' => '#AC0100',
        'retire' => '#9AA6B8',
    ];

    private const STADES = [
        'idee' => '#7C3AED',
        'developpement' => '#4F6FBF',
        'lance' => '#1C8F4C',
    ];

    public static function color(string $statut): string
    {
        return self::STATUTS[$statut] ?? '#4F6FBF';
    }

    public static function stade(string $stade): string
    {
        return self::STADES[$stade] ?? '#4F6FBF';
    }
}
