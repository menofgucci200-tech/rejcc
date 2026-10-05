<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.token'   => \App\Http\Middleware\AuthenticateToken::class,
            'auth.admin'   => \App\Http\Middleware\RequireAdmin::class,
            'audit.log'    => \App\Http\Middleware\LogAdminActivity::class,
            'sub.active'   => \App\Http\Middleware\RequireSubscription::class,
        ]);

        // Derriere le proxy HTTPS de l'hebergeur (Render, etc.), sans quoi
        // Laravel genere des URLs/redirections en http:// et casse la session.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Trop de tentatives (connexion, formulaires…) : message en français
        // avec le délai d'attente, au lieu de « Too Many Attempts. ».
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
            $secondes = (int) ($e->getHeaders()['Retry-After'] ?? 60);

            return response()->json([
                'ok' => false,
                'message' => 'Trop de tentatives. Patientez '.($secondes >= 60 ? ceil($secondes / 60).' minute'.($secondes >= 120 ? 's' : '') : $secondes.' secondes').' avant de réessayer.',
            ], 429, $e->getHeaders());
        });
    })->create();
