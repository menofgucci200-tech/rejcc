<?php

namespace App\Support;

/** Couleurs des types et des statuts d'une offre d'emploi ou de stage. */
class OffreStatus
{
    public const TYPES = [
        'emploi' => '#031D59',
        'stage' => '#B27007',
        'alternance' => '#7C3AED',
        'freelance' => '#1C8F4C',
        'mission' => '#4F6FBF',
    ];

    public const STATUTS = [
        'en_attente' => '#4F6FBF',
        'a_corriger' => '#B27007',
        'publiee' => '#1C8F4C',
        'refusee' => '#AC0100',
        'pourvue' => '#7C3AED',
        'cloturee' => '#9AA6B8',
        'expiree' => '#9AA6B8',
    ];

    public static function type(?string $t): string
    {
        return self::TYPES[$t] ?? '#4F6FBF';
    }

    public static function statut(?string $s): string
    {
        return self::STATUTS[$s] ?? '#4F6FBF';
    }
}
