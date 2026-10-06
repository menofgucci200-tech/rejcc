<?php

namespace App\Http\Controllers;

use App\Support\Api;

/**
 * Page « Galerie » du site vitrine : albums photo de la vie du réseau.
 */
class GaleriePublicController extends Controller
{
    public function index()
    {
        $albums = collect(Api::get('/albums')['albums'] ?? []);
        // Photos hors album (anciennes photos de la galerie d'accueil).
        $photos = $albums->isEmpty() ? collect(Api::get('/gallery')['photos'] ?? []) : collect();

        return view('pages.galerie.index', compact('albums', 'photos'));
    }

    public function show(string $slug)
    {
        $data = Api::get('/albums/'.rawurlencode($slug));
        abort_unless($data['ok'] ?? false, 404);

        $autres = collect(Api::get('/albums')['albums'] ?? [])->where('slug', '!=', $slug)->take(3);

        return view('pages.galerie.show', [
            'album' => $data['album'],
            'photos' => collect($data['photos'] ?? []),
            'autres' => $autres,
        ]);
    }
}
