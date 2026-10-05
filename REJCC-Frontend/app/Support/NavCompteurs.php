<?php

namespace App\Support;

/**
 * Pastilles du menu (messages non lus, actions de mentorat en attente),
 * mémorisées 20 s en session pour ne pas appeler l'API à chaque rendu.
 */
class NavCompteurs
{
    public static function get(): array
    {
        $cache = session('nav_compteurs');
        if (is_array($cache) && ($cache['at'] ?? 0) > time() - 20) {
            return $cache['data'];
        }

        $data = Api::get('/nav-compteurs', [], Api::token())['compteurs'] ?? ['messages' => 0, 'mentorat' => 0];
        session(['nav_compteurs' => ['at' => time(), 'data' => $data]]);

        return $data;
    }

    /** À appeler quand une action change un compteur (message lu, demande traitée…). */
    public static function oublier(): void
    {
        session()->forget('nav_compteurs');
    }
}
