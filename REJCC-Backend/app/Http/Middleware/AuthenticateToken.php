<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
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

        $row->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $row->user);

        return $next($request);
    }
}
