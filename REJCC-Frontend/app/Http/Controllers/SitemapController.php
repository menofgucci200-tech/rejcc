<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke()
    {
        // Toujours le domaine officiel (APP_URL), même si le plan est demandé via www.
        $base = rtrim((string) config('app.url'), '/');
        $u = fn (string $chemin) => $chemin === '/' ? $base.'/' : $base.$chemin;

        $xml = Cache::remember('sitemap.xml', 3600, function () use ($u) {
            $urls = [
                ['loc' => $u('/'), 'priority' => '1.0'],
                ['loc' => $u('/a-propos'), 'priority' => '0.8'],
                ['loc' => $u('/activites'), 'priority' => '0.8'],
                ['loc' => $u('/domaines'), 'priority' => '0.7'],
                ['loc' => $u('/evenements'), 'priority' => '0.8'],
                ['loc' => $u('/actualites'), 'priority' => '0.8'],
                ['loc' => $u('/partenaires'), 'priority' => '0.6'],
                ['loc' => $u('/galerie'), 'priority' => '0.6'],
                ['loc' => $u('/adhesion'), 'priority' => '0.9'],
                ['loc' => $u('/contact'), 'priority' => '0.6'],
            ];

            foreach (\App\Support\Content\LegalPages::SLUGS as $slug) {
                $urls[] = ['loc' => $u('/'.$slug), 'priority' => '0.3'];
            }

            foreach (Api::get('/news')['articles'] ?? [] as $article) {
                $urls[] = ['loc' => $u('/actualites/'.$article['slug']), 'priority' => '0.6', 'lastmod' => $article['published_at'] ?? null];
            }

            foreach (Api::get('/albums')['albums'] ?? [] as $album) {
                $urls[] = ['loc' => $u('/galerie/'.$album['slug']), 'priority' => '0.5', 'lastmod' => $album['date'] ?? null];
            }

            $urls[] = ['loc' => $u('/projets'), 'priority' => '0.6'];
            $urls[] = ['loc' => $u('/emplois'), 'priority' => '0.6'];
            foreach (Api::get('/public-opportunities')['opportunities'] ?? [] as $offre) {
                $urls[] = ['loc' => $u('/emplois/'.$offre['id']), 'priority' => '0.5', 'lastmod' => $offre['publie_at'] ?? null];
            }
            foreach (Api::get('/public-projects')['projects'] ?? [] as $projet) {
                $urls[] = ['loc' => $u('/projets/'.$projet['id']), 'priority' => '0.5'];
            }

            foreach (Api::get('/public-events')['events'] ?? [] as $event) {
                $urls[] = ['loc' => $u('/evenements/'.$event['slug']), 'priority' => '0.6'];
            }

            $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
            foreach ($urls as $url) {
                $lastmod = '';
                if (! empty($url['lastmod'])) {
                    try {
                        $lastmod = '<lastmod>'.\Illuminate\Support\Carbon::parse($url['lastmod'])->toDateString().'</lastmod>';
                    } catch (\Throwable) {
                    }
                }
                $lines[] = '  <url><loc>'.e($url['loc']).'</loc>'.$lastmod.'<priority>'.$url['priority'].'</priority></url>';
            }
            $lines[] = '</urlset>';

            return implode("\n", $lines);
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
