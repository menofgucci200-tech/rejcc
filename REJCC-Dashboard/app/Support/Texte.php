<?php

namespace App\Support;

/**
 * Mise en forme sûre d'un texte saisi par un membre : échappement HTML,
 * puis liens (http(s)://… et www.…) rendus cliquables.
 */
class Texte
{
    public static function liens(?string $texte, string $classe = 'underline underline-offset-2'): string
    {
        $sur = e((string) $texte);

        return preg_replace_callback(
            '~\b(https?://|www\.)[^\s<]+[^\s<.,;:!?)\]"\'&]~iu',
            function ($m) use ($classe) {
                $url = html_entity_decode($m[0], ENT_QUOTES);
                $href = str_starts_with(strtolower($url), 'www.') ? 'https://'.$url : $url;

                return '<a href="'.e($href).'" target="_blank" rel="noopener nofollow" class="'.$classe.'">'.$m[0].'</a>';
            },
            $sur
        );
    }

    /** « il y a 5 minutes », et « à l'instant » pour moins d'une minute. */
    public static function depuis(?string $date): string
    {
        if (! $date) {
            return '';
        }
        $d = \Illuminate\Support\Carbon::parse($date)->locale('fr');

        return $d->diffInSeconds(now()) < 60 ? "à l'instant" : $d->diffForHumans();
    }
}
