<?php

namespace App\Support;

/**
 * Transforme le lien vidéo d'un module en lecteur intégré à la plateforme :
 * YouTube et Vimeo en iframe, fichier vidéo (mp4, webm…) en lecteur natif.
 * Un lien d'un autre type reste un simple lien.
 */
class VideoEmbed
{
    /** @return array{type: string, src: string}|null */
    public static function from(?string $url): ?array
    {
        if (! $url) {
            return null;
        }

        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return ['type' => 'iframe', 'src' => "https://www.youtube-nocookie.com/embed/{$m[1]}?rel=0&modestbranding=1"];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return ['type' => 'iframe', 'src' => "https://player.vimeo.com/video/{$m[1]}"];
        }

        if (preg_match('~\.(mp4|webm|ogg|mov|m4v)(\?.*)?$~i', $url)) {
            return ['type' => 'fichier', 'src' => $url];
        }

        return ['type' => 'lien', 'src' => $url];
    }
}
