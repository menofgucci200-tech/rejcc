<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = session('api_user');

        if ($user) {
            return redirect(($user['role'] ?? null) === 'admin' ? '/admin' : '/espace-membre');
        }

        return $next($request);
    }
}
