<?php

namespace App\Http\Middleware;

use App\Support\Audience;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compte les pages vues du site (mesure anonyme, sans cookie : voir
 * App\Support\Audience). Le comptage se fait après l'envoi de la page au
 * visiteur, il ne la ralentit pas.
 */
class MesureAudience
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (Audience::aCompter($request, $response)) {
                Audience::enregistrer($request, Audience::chemin('/'.ltrim($request->path(), '/')));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
