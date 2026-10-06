<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = [
                ['loc' => url('/'), 'priority' => '1.0'],
                ['loc' => url('/a-propos'), 'priority' => '0.8'],
                ['loc' => url('/activites'), 'priority' => '0.8'],
                ['loc' => url('/domaines'), 'priority' => '0.7'],
                ['loc' => url('/evenements'), 'priority' => '0.8'],
                ['loc' => url('/actualites'), 'priority' => '0.8'],
                ['loc' => url('/partenaires'), 'priority' => '0.6'],
                ['loc' => url('/adhesion'), 'priority' => '0.9'],
                ['loc' => url('/contact'), 'priority' => '0.6'],
            ];

            foreach (\App\Support\Content\LegalPages::SLUGS as $slug) {
                $urls[] = ['loc' => url('/'.$slug), 'priority' => '0.3'];
            }

            foreach (Api::get('/news')['articles'] ?? [] as $article) {
                $urls[] = ['loc' => url('/actualites/'.$article['slug']), 'priority' => '0.6'];
            }

            $urls[] = ['loc' => url('/projets'), 'priority' => '0.6'];
            $urls[] = ['loc' => url('/emplois'), 'priority' => '0.6'];
            foreach (Api::get('/public-opportunities')['opportunities'] ?? [] as $offre) {
                $urls[] = ['loc' => url('/emplois/'.$offre['id']), 'priority' => '0.5'];
            }
            foreach (Api::get('/public-projects')['projects'] ?? [] as $projet) {
                $urls[] = ['loc' => url('/projets/'.$projet['id']), 'priority' => '0.5'];
            }

            foreach (Api::get('/public-events')['events'] ?? [] as $event) {
                $urls[] = ['loc' => url('/evenements/'.$event['slug']), 'priority' => '0.6'];
            }

            $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
            foreach ($urls as $url) {
                $lines[] = '  <url><loc>'.e($url['loc']).'</loc><priority>'.$url['priority'].'</priority></url>';
            }
            $lines[] = '</urlset>';

            return implode("\n", $lines);
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
