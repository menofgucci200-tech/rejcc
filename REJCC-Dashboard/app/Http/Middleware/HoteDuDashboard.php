<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le dashboard ne répond que sur sa propre adresse (APP_URL,
 * dashboard.rejcc.site). Sur l'hébergement, son dossier public est aussi
 * joignable par rejcc.site/dashboard : ces requêtes reçoivent une page
 * introuvable neutre, qui ne révèle pas l'existence du dashboard.
 */
class HoteDuDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $hote = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! app()->environment('local', 'testing') && $hote && strcasecmp($request->getHost(), $hote) !== 0) {
            return response('Not Found', 404, ['Content-Type' => 'text/plain; charset=UTF-8', 'X-Robots-Tag' => 'noindex']);
        }

        return $next($request);
    }
}
