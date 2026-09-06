<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve une fonctionnalité aux membres dont l'abonnement annuel
 * (10 000 F) est à jour. Les administrateurs y ont toujours accès
 * (cf. User::hasActiveSubscription()).
 */
class RequireSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasActiveSubscription()) {
            return response()->json([
                'ok' => false,
                'code' => 'subscription_required',
                'message' => "Cette fonctionnalité est réservée aux membres à jour de leur abonnement annuel (10 000 F).",
            ], 402);
        }

        return $next($request);
    }
}
