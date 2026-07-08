<?php

namespace App\Http\Middleware;

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

        return $next($request);
    }
}
