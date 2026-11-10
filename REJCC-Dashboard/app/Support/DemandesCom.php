<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Aides d'affichage des demandes de communication, partagées par la page de
 * la Communication, celle du demandeur et le bloc de l'espace Communication.
 */
class DemandesCom
{
    /** [couleur du texte, fond] du badge de statut. */
    public static function teinte(?string $statut): array
    {
        return match ($statut) {
            'nouvelle' => ['#B26A00', '#FDF2E3'],
            'acceptee' => ['#2D5C8F', '#EEF6FD'],
            'planifiee' => ['#5B3FA8', '#F1EDFB'],
            'publiee' => ['#1C8A49', '#EAF6EE'],
            'refusee' => ['#8F0100', '#FDEEEE'],
            default => ['#5B677A', '#F1F3F7'],
        };
    }

    public static function date(?string $iso, string $format = 'j M Y'): string
    {
        return $iso ? Carbon::parse($iso)->locale('fr')->translatedFormat($format) : '';
    }

    /** Échéance lisible : « dans 3 jours », « aujourd'hui », « dépassée de 2 jours ». */
    public static function echeance(?int $jours): string
    {
        if ($jours === null) {
            return '';
        }

        return match (true) {
            $jours < 0 => 'dépassée de '.abs($jours).' jour'.(abs($jours) > 1 ? 's' : ''),
            $jours === 0 => 'aujourd\'hui',
            $jours === 1 => 'demain',
            default => "dans {$jours} jours",
        };
    }

    /** Classes du badge d'échéance selon le niveau de délai (court, juste, confortable). */
    public static function classesDelai(?string $niveau): string
    {
        return match ($niveau) {
            'court' => 'bg-[#FDEEEE] text-[#8F0100]',
            'juste' => 'bg-[#FDF2E3] text-[#8A5A12]',
            default => 'bg-[#F1F3F7] text-[#5B677A]',
        };
    }
}
