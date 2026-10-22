<?php

namespace App\Http\Middleware;

use App\Support\AdminSections;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = session('api_user');
        // Compte qui n'est plus (ou n'a jamais été) d'administration : on ferme
        // la session du dashboard, il retourne à la connexion.
        if (! \App\Support\Espace::autorise($user)) {
            $request->session()->invalidate();

            return redirect()->route('login')->with('status', 'Votre compte n\'a pas accès au dashboard.');
        }

        // Admin à accès restreint : seules ses sections lui sont visibles.
        $routeName = $request->route()?->getName();
        if ($routeName && ! AdminSections::allowedRoute($user, $routeName)) {
            abort(403, 'Votre compte administrateur n\'a pas accès à cette section.');
        }

        return $next($request);
    }
}
