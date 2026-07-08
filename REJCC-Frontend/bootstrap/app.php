<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.web' => \App\Http\Middleware\RequireAdminWeb::class,
            'api.auth' => \App\Http\Middleware\EnsureApiAuthenticated::class,
            'api.guest' => \App\Http\Middleware\EnsureApiGuest::class,
        ]);

        // Derriere le proxy HTTPS de l'hebergeur (Render, etc.), sans quoi
        // Laravel genere des URLs/redirections en http:// et casse la session.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
