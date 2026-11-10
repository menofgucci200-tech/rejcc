<?php

namespace App\Http\Controllers;

use App\Support\Api;
use App\Support\Audience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Mesure d'audience et liens de suivi de la Communication.
 */
class MesureController extends Controller
{
    /**
     * POST /mesure : page ouverte par wire:navigate (signalée par le navigateur
     * au moment du clic, les préchargements au survol ne comptent donc pas).
     * Seules les vraies pages du site sont retenues.
     */
    public function vue(Request $request)
    {
        $chemin = (string) parse_url(mb_substr((string) $request->input('chemin'), 0, 300), PHP_URL_PATH);
        if (str_starts_with($chemin, '/') && ! Audience::estRobot($request->userAgent()) && ($normalise = Audience::chemin($chemin)) !== null) {
            try {
                $route = Route::getRoutes()->match(Request::create($chemin, 'GET'));
                if (in_array('GET', $route->methods(), true)) {
                    Audience::enregistrer($request, $normalise, false);
                }
            } catch (HttpException) {
                // Adresse inconnue : rien à compter.
            }
        }

        return response()->noContent();
    }

    /**
     * GET /l/{code} : lien de suivi. L'API donne l'adresse enregistrée par la
     * Com et compte le clic ; un code inconnu ou désactivé ramène à l'accueil.
     * Jamais de redirection vers une adresse venue de la requête.
     */
    public function lien(Request $request, string $code)
    {
        $robot = Audience::estRobot($request->userAgent()) || Audience::estPrechargement($request) || $request->method() !== 'GET';
        try {
            $r = Api::get('/liens/'.rawurlencode($code), $robot ? ['compter' => '0'] : []);
        } catch (\Throwable $e) {
            report($e);
            $r = [];
        }
        $cible = $r['cible'] ?? null;
        if (! is_string($cible) || ! preg_match('#^https?://#i', $cible)) {
            return redirect('/', 302);
        }

        return redirect()->away(self::avecSource($cible, (string) ($r['canal'] ?? '')), 302);
    }

    /**
     * Vers une page du site : ajoute utm_source={canal} (si absent) pour que la
     * visite apparaisse avec sa provenance dans les statistiques.
     */
    private static function avecSource(string $cible, string $canal): string
    {
        $hote = strtolower((string) parse_url($cible, PHP_URL_HOST));
        $site = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $interne = $hote !== '' && in_array(preg_replace('/^www\./', '', $hote), array_filter([preg_replace('/^www\./', '', $site), request()->getHost()]), true);
        if (! $interne || $canal === '' || preg_match('/[?&]utm_source=/', $cible)) {
            return $cible;
        }
        [$base, $ancre] = array_pad(explode('#', $cible, 2), 2, null);

        return $base.(str_contains($base, '?') ? '&' : '?').'utm_source='.rawurlencode($canal).($ancre !== null ? '#'.$ancre : '');
    }
}
