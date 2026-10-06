<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dates rédigées par l'API (fil d'activité, graphiques admin…) en français,
        // quelle que soit la langue technique de l'application.
        Carbon::setLocale('fr');

        // Vérification des certificats : le site appelle l'API pour ses visiteurs
        // et transmet l'empreinte de leur adresse (X-Visiteur). Limite par
        // visiteur contre les essais en série, plus un plafond global par adresse.
        RateLimiter::for('verification', fn (Request $r) => [
            Limit::perMinute(20)->by('visiteur:'.(preg_match('/^[a-f0-9]{64}$/', (string) $r->header('X-Visiteur')) ? $r->header('X-Visiteur') : $r->ip())),
            Limit::perMinute(600)->by('adresse:'.$r->ip()),
        ]);
    }
}
