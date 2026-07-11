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
        if (! $user || ($user['role'] ?? null) !== 'admin') {
            abort(403);
        }

        // Admin à accès restreint : seules ses sections lui sont visibles.
        $routeName = $request->route()?->getName();
        if ($routeName && ! AdminSections::allowedRoute($user, $routeName)) {
            abort(403, 'Votre compte administrateur n\'a pas accès à cette section.');
        }

        return $next($request);
    }
}
