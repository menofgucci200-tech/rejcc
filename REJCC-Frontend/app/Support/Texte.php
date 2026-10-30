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

    /**
     * Message de la Communauté : liens cliquables, mentions @Prénom Nom et
     * @tous mises en évidence, retours à la ligne conservés.
     */
    public static function message(?string $texte, string $classeLien = 'underline underline-offset-2', string $classeMention = 'font-bold text-azure'): string
    {
        $html = self::liens($texte, $classeLien);
        $html = preg_replace(
            '/(?<![\p{L}\p{N}@])@(tous\b|[\p{Lu}][\p{L}\-]+(?: [\p{Lu}][\p{L}\-]+)?)/u',
            '<span class="'.$classeMention.'">@$1</span>',
            $html
        );

        return nl2br($html, false);
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
