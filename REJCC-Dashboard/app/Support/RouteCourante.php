<?php

namespace App\Support;

use Illuminate\Http\Request;
use Livewire\Livewire;

/**
 * Nom de la route de la page affichée, y compris pendant une mise à jour
 * Livewire (où la requête courante est celle de /livewire/update) : on
 * retrouve alors la route à partir de l'URL d'origine de la page.
 */
class RouteCourante
{
    public static function nom(): string
    {
        if (! Livewire::isLivewireRequest()) {
            return request()->route()?->getName() ?? '';
        }

        try {
            return app('router')->getRoutes()->match(Request::create(Livewire::originalUrl()))->getName() ?? '';
        } catch (\Throwable) {
            return '';
        }
    }
}
