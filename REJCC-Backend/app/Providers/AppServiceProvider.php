<?php

namespace App\Providers;

use App\Support\Client;
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
        // Connexion, inscription, mot de passe oublié : le site appelle l'API depuis
        // son propre serveur, la limite se fait donc par visiteur réel (IP
        // transmise) et par adresse e-mail visée, pas par IP du serveur.
        RateLimiter::for('connexion', fn (Request $r) => [
            Limit::perMinute(8)->by('ip:'.Client::ip($r)),
            Limit::perMinute(5)->by('email:'.mb_strtolower((string) $r->input('email'))),
            Limit::perMinute(300)->by('global'),
        ]);
        // Paramètres du compte : une limite propre à chaque action sensible (les
        // limites « throttle:x,y » partageraient un même compteur entre routes).
        $parCompte = fn (Request $r) => (string) ($r->user()?->id ?: Client::ip($r));
        RateLimiter::for('compte-mdp', fn (Request $r) => Limit::perMinute(10)->by($parCompte($r)));
        RateLimiter::for('compte-email', fn (Request $r) => Limit::perMinutes(10, 5)->by($parCompte($r)));
        RateLimiter::for('compte-export', fn (Request $r) => Limit::perHour(10)->by($parCompte($r)));
        RateLimiter::for('compte-cloture', fn (Request $r) => Limit::perMinutes(10, 5)->by($parCompte($r)));
        RateLimiter::for('compte-push', fn (Request $r) => Limit::perMinute(5)->by($parCompte($r)));

        RateLimiter::for('verification', fn (Request $r) => [
            Limit::perMinute(20)->by('visiteur:'.(preg_match('/^[a-f0-9]{64}$/', (string) $r->header('X-Visiteur')) ? $r->header('X-Visiteur') : $r->ip())),
            Limit::perMinute(600)->by('adresse:'.$r->ip()),
        ]);
    }
}
