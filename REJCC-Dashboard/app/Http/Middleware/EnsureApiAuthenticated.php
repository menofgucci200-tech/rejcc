<?php

namespace App\Http\Middleware;

use App\Support\Api;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAuthenticated
{
    /** Délai au-delà duquel l'utilisateur en session est relu depuis l'API. */
    private const RAFRAICHIR_APRES = 60;

    public function handle(Request $request, Closure $next): Response
    {
        if (! session('api_token')) {
            // Après connexion, on revient sur la page demandée (lien d'une notification, d'un QR…).
            if ($request->isMethod('GET') && ! $request->header('X-Livewire')) {
                session(['url.intended' => $request->getRequestUri()]);
            }

            return redirect()->route('login');
        }

        // L'utilisateur en session date de la connexion : on le relit au plus
        // toutes les minutes, pour que les changements faits ailleurs (abonnement
        // payé ou expiré, abonnements activés/désactivés par l'admin…) s'appliquent
        // sans avoir à se reconnecter.
        if (now()->timestamp - (int) session('api_user_synced_at', 0) >= self::RAFRAICHIR_APRES) {
            $me = Api::get('/auth/me', [], Api::token());
            if ($me['ok'] ?? false) {
                session(['api_user' => $me['user'], 'api_user_synced_at' => now()->timestamp]);
            }
        }

        return $next($request);
    }
}
