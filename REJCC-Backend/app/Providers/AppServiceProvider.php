<?php

namespace App\Providers;

use Carbon\Carbon;
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
    }
}
