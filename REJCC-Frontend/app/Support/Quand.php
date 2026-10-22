<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Délai lisible avant un rendez-vous : « dans 25 min », « dans 2 h », « demain à 18h00 ». */
class Quand
{
    public static function delai(string $iso): string
    {
        $debut = Carbon::parse($iso)->setTimezone(config('app.timezone'));
        $minutes = (int) now()->diffInMinutes($debut, false);

        return match (true) {
            $minutes <= 0 => 'maintenant',
            $minutes < 60 => "dans {$minutes} min",
            $minutes < 180 => 'dans '.intdiv($minutes, 60).' h'.($minutes % 60 ? ' '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) : ''),
            $debut->isToday() => "aujourd'hui à ".$debut->format('H\hi'),
            $debut->isTomorrow() => 'demain à '.$debut->format('H\hi'),
            default => $debut->locale('fr')->isoFormat('dddd D MMMM [à] HH[h]mm'),
        };
    }
}
