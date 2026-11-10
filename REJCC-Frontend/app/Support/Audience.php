<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mesure d'audience du site, anonyme et sans cookie (pas de bannière de
 * consentement) : un fichier JSON par jour dans le stockage privé partagé
 * (statistiques/visites/AAAA-MM-JJ.json), lu directement par le dashboard
 * (App\Support\StatistiquesSite).
 *
 * Vie privée : aucune adresse IP ni navigateur n'est conservé. Un visiteur
 * unique du jour est reconnu par une empreinte courte = HMAC(IP + navigateur,
 * sel du jour). Le sel est aléatoire, tiré chaque jour et rangé hors du
 * dossier partagé (storage/framework/audience) ; il est détruit le lendemain
 * avec les empreintes, qui ne servent qu'au comptage du jour même.
 *
 * Performance : appelée après l'envoi de la page (middleware « terminate »),
 * une lecture et une écriture d'un petit fichier sous verrou ; toute erreur
 * est avalée, la mesure ne casse jamais une page.
 */
class Audience
{
    /** Agrégats conservés environ 13 mois. */
    public const JOURS_CONSERVES = 400;

    /** Plafonds par jour (protège le fichier contre les adresses fantaisistes). */
    private const MAX_PAGES = 300;

    private const MAX_SOURCES = 100;

    /** Premières parties d'adresse jamais comptées (espace membre, liens personnels, technique). */
    private const EXCLUS = [
        'espace-membre', 'mon-espace', 'animer', 'confirmer-email', 'reinitialiser-mot-de-passe', 'billet',
        'livewire', 'l', 'mesure', 'up', 'sitemap.xml', 'storage', 'build',
    ];

    /** Pages dont la deuxième partie est un code personnel (carte membre, certificat). */
    private const AVEC_CODE = ['carte', 'verifier'];

    private const ROBOTS = '/bot\b|bot\/|crawl|spider|slurp|facebookexternalhit|facebookcatalog|whatsapp|telegram|preview|curl|wget|python|http-?client|axios|node-fetch|go-http|java\/|okhttp|libwww|scrapy|headless|lighthouse|pingdom|uptime|monitor|semrush|ahrefs|mj12|petalbot|yandex|baidu|embedly|vkshare|discord|linkedin|twitter|applebot|inspectiontool|feedfetcher|guzzle|php\//i';

    /** La réponse correspond-elle à une vraie page vue par une personne ? */
    public static function aCompter(Request $request, Response $response): bool
    {
        return $request->method() === 'GET'
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            // Mises à jour Livewire et pages ouvertes par wire:navigate : ces dernières
            // peuvent être des préchargements au survol, elles sont signalées à part
            // par le navigateur au moment réel de la navigation (POST /mesure).
            && ! $request->headers->has('X-Livewire')
            && ! $request->headers->has('X-Livewire-Navigate')
            && ! self::estPrechargement($request)
            && ! self::estRobot($request->userAgent())
            && self::chemin('/'.ltrim($request->path(), '/')) !== null;
    }

    public static function estPrechargement(Request $request): bool
    {
        $entetes = strtolower($request->header('Sec-Purpose', '').' '.$request->header('Purpose', '').' '.$request->header('X-Purpose', '').' '.$request->header('X-Moz', ''));

        return str_contains($entetes, 'prefetch') || str_contains($entetes, 'prerender') || str_contains($entetes, 'preview');
    }

    public static function estRobot(?string $navigateur): bool
    {
        return trim((string) $navigateur) === '' || preg_match(self::ROBOTS, (string) $navigateur) === 1;
    }

    /**
     * Chemin normalisé (identifiants remplacés), ou null s'il ne se compte pas.
     * /emplois/42 → /emplois/{id} ; /carte/AB12CD → /carte/{code}.
     */
    public static function chemin(string $chemin): ?string
    {
        $chemin = strtolower((string) parse_url($chemin, PHP_URL_PATH));
        $parties = array_values(array_filter(explode('/', $chemin), fn ($p) => $p !== ''));
        if ($parties !== [] && in_array($parties[0], self::EXCLUS, true)) {
            return null;
        }
        foreach ($parties as $i => $p) {
            if (ctype_digit($p)) {
                $parties[$i] = '{id}';
            } elseif (($i > 0 && in_array($parties[0], self::AVEC_CODE, true)) || preg_match('/^[a-z0-9]{16,}$/', $p) || strlen($p) > 80) {
                $parties[$i] = '{code}';
            }
        }

        return mb_substr('/'.implode('/', $parties), 0, 200);
    }

    /** Compte une page vue. $chemin déjà normalisé ; $request pour l'empreinte et la source. */
    public static function enregistrer(Request $request, string $chemin, bool $avecSource = true): void
    {
        try {
            $jour = now()->toDateString();
            self::sousVerrou(function () use ($request, $chemin, $avecSource, $jour) {
                $fichier = self::dossier().'/'.$jour.'.json';
                $existant = is_file($fichier) ? json_decode((string) file_get_contents($fichier), true) : null;
                if (! is_array($existant)) {
                    // Premier passage du jour : ménage des jours précédents.
                    self::maintenanceSansVerrou($jour);
                }
                $d = is_array($existant) ? $existant : [
                    'jour' => $jour, 'pages_vues' => 0, 'visiteurs' => 0,
                    'pages' => [], 'sources' => [], 'appareils' => ['telephone' => 0, 'ordinateur' => 0], 'empreintes' => [],
                ];

                $d['pages_vues']++;
                $page = self::plafonner($d['pages'], $chemin, self::MAX_PAGES, '(autres pages)');
                $d['pages'][$page] = ($d['pages'][$page] ?? 0) + 1;

                $empreinte = substr(hash_hmac('sha256', $request->ip().'|'.$request->userAgent(), self::sel($jour)), 0, 16);
                $nouveau = ! isset($d['empreintes'][$empreinte]);
                if ($nouveau) {
                    $d['empreintes'][$empreinte] = 1;
                    $d['visiteurs']++;
                    $appareil = preg_match('/Mobi|Android|iPhone|iPad|iPod|Opera Mini|IEMobile|Tablet|Silk|Kindle/i', (string) $request->userAgent()) ? 'telephone' : 'ordinateur';
                    $d['appareils'][$appareil] = ($d['appareils'][$appareil] ?? 0) + 1;
                }

                // Source : lien externe ou utm_source à chaque arrivée, « direct » pour un nouveau visiteur sans provenance.
                $source = $avecSource ? self::source($request) : null;
                if ($source === null && $nouveau && $avecSource) {
                    $source = 'direct';
                }
                if ($source !== null) {
                    $cle = self::plafonner($d['sources'], $source, self::MAX_SOURCES, '(autres)');
                    $d['sources'][$cle] = ($d['sources'][$cle] ?? 0) + 1;
                }

                self::ecrire($fichier, $d);
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Provenance d'une arrivée : utm_source, sinon domaine du site d'origine (externe), sinon null. */
    public static function source(Request $request): ?string
    {
        $utm = preg_replace('/[^a-z0-9._-]/', '', strtolower((string) $request->query('utm_source')));
        if ($utm !== '' && $utm !== null) {
            return mb_substr($utm, 0, 40);
        }
        $hote = strtolower((string) parse_url((string) $request->headers->get('referer'), PHP_URL_HOST));
        if ($hote === '') {
            return null;
        }
        $hote = preg_replace('/^(www|m|l|lm|mobile|mbasic|web)\./', '', $hote);
        $internes = array_filter([$request->getHost(), parse_url((string) config('app.url'), PHP_URL_HOST)]);
        foreach ($internes as $interne) {
            if ($hote === preg_replace('/^www\./', '', strtolower($interne))) {
                return null;
            }
        }
        if (preg_match('/(^|\.)google\.[a-z.]+$/', $hote)) {
            return 'google';
        }

        return mb_substr($hote, 0, 60);
    }

    /** Ménage : empreintes des jours passés effacées, agrégats de plus de ~13 mois supprimés, anciens sels détruits. */
    public static function maintenance(): array
    {
        $bilan = ['empreintes' => 0, 'supprimes' => 0];
        self::sousVerrou(function () use (&$bilan) {
            $bilan = self::maintenanceSansVerrou(now()->toDateString());
        });

        return $bilan;
    }

    private static function maintenanceSansVerrou(string $aujourdhui): array
    {
        $bilan = ['empreintes' => 0, 'supprimes' => 0];
        $limite = now()->subDays(self::JOURS_CONSERVES)->toDateString();
        foreach (glob(self::dossier().'/*.json') ?: [] as $f) {
            $jour = basename($f, '.json');
            if ($jour >= $aujourdhui) {
                continue;
            }
            if ($jour < $limite) {
                @unlink($f) && $bilan['supprimes']++;

                continue;
            }
            $brut = (string) @file_get_contents($f);
            if (str_contains($brut, '"empreintes"') && is_array($d = json_decode($brut, true))) {
                unset($d['empreintes']);
                self::ecrire($f, $d);
                $bilan['empreintes']++;
            }
        }
        foreach (glob(self::dossierSel().'/sel-*') ?: [] as $f) {
            if (basename($f) !== 'sel-'.$aujourdhui) {
                @unlink($f);
            }
        }

        return $bilan;
    }

    /** Sel aléatoire du jour, hors du dossier partagé avec le dashboard. */
    private static function sel(string $jour): string
    {
        $f = self::dossierSel().'/sel-'.$jour;
        $sel = is_file($f) ? trim((string) file_get_contents($f)) : '';
        if (strlen($sel) < 32) {
            $sel = bin2hex(random_bytes(32));
            file_put_contents($f, $sel);
            @chmod($f, 0600);
        }

        return $sel;
    }

    /** Au-delà du plafond, les nouvelles clés sont regroupées sous $autre. */
    private static function plafonner(array $liste, string $cle, int $max, string $autre): string
    {
        return isset($liste[$cle]) || count($liste) < $max ? $cle : $autre;
    }

    private static function sousVerrou(callable $f): void
    {
        $verrou = @fopen(self::dossier().'/.verrou', 'c');
        if (! $verrou) {
            return;
        }
        try {
            if (flock($verrou, LOCK_EX)) {
                $f();
            }
        } finally {
            flock($verrou, LOCK_UN);
            fclose($verrou);
        }
    }

    /** Écriture atomique (fichier temporaire puis renommage) : jamais de fichier à moitié écrit. */
    private static function ecrire(string $fichier, array $d): void
    {
        $tmp = $fichier.'.'.getmypid().'.tmp';
        if (file_put_contents($tmp, json_encode($d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !== false) {
            rename($tmp, $fichier);
        }
    }

    public static function dossier(): string
    {
        $d = Storage::disk('local')->path('statistiques/visites');
        is_dir($d) || @mkdir($d, 0775, true);

        return $d;
    }

    private static function dossierSel(): string
    {
        $d = storage_path('framework/audience');
        is_dir($d) || @mkdir($d, 0700, true);

        return $d;
    }
}
