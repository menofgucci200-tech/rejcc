<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdmin
{
    /**
     * @param  string|null  $section  Section d'administration requise. Un admin
     *                                dont `permissions` est null a accès à tout ;
     *                                sinon la section doit figurer dans sa liste.
     */
    public function handle(Request $request, Closure $next, ?string $section = null): Response
    {
        $user = $request->user();
        if (! $user || $user->role !== 'admin') {
            return response()->json(['ok' => false, 'message' => 'Accès réservé aux administrateurs.'], 403);
        }

        if ($section !== null && is_array($user->permissions) && ! in_array($section, $user->permissions, true)) {
            return response()->json(['ok' => false, 'message' => 'Votre compte administrateur n\'a pas accès à cette section.'], 403);
        }

        return $next($request);
    }
}
