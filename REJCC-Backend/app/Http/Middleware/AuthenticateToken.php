<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Support\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    /** Durée d'inactivité (en jours) au-delà de laquelle un token est invalidé. */
    public const IDLE_DAYS = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        if (! $plain) {
            return response()->json(['ok' => false, 'message' => 'Non authentifié.'], 401);
        }

        $row = ApiToken::where('token', hash('sha256', $plain))->first();
        if (! $row) {
            return response()->json(['ok' => false, 'message' => 'Session expirée, veuillez vous reconnecter.'], 401);
        }

        if (($row->last_used_at ?? $row->created_at)->lt(now()->subDays(self::IDLE_DAYS))) {
            $row->delete();

            return response()->json(['ok' => false, 'message' => 'Session expirée, veuillez vous reconnecter.'], 401);
        }

        // Appareil et adresse tenus à jour pour « Appareils connectés ».
        $row->forceFill(['last_used_at' => now()] + array_filter([
            'ip' => Client::ip($request),
            'agent' => Client::agent($request),
        ]))->save();
        $request->attributes->set('api_token_id', $row->id);
        $request->setUserResolver(fn () => $row->user);

        return $next($request);
    }
}
