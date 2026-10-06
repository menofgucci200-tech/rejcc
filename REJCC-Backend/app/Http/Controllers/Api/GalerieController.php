<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;

/**
 * Galerie publique du site vitrine : albums publiés et leurs photos.
 */
class GalerieController extends Controller
{
    public function albums()
    {
        $albums = GalleryAlbum::where('publie', true)
            ->whereHas('photos')
            ->withCount('photos')
            ->orderByDesc('date_evenement')->orderBy('ordre')->orderByDesc('id')
            ->get()
            ->map(fn (GalleryAlbum $a) => $this->resume($a));

        return response()->json(['ok' => true, 'albums' => $albums]);
    }

    public function album(string $slug)
    {
        $album = GalleryAlbum::where('slug', $slug)->where('publie', true)->withCount('photos')->first();
        if (! $album) {
            return response()->json(['ok' => false, 'message' => 'Album introuvable.'], 404);
        }

        return response()->json([
            'ok' => true,
            'album' => $this->resume($album) + ['description' => $album->description],
            'photos' => $album->photos()->get(['id', 'url', 'caption']),
        ]);
    }

    private function resume(GalleryAlbum $a): array
    {
        return [
            'slug' => $a->slug,
            'titre' => $a->titre,
            'date' => $a->date_evenement?->format('Y-m-d'),
            'lieu' => $a->lieu,
            'couverture' => $a->couvertureUrl(),
            'photos' => $a->photos_count,
            'apercu' => $a->photos()->limit(4)->pluck('url'),
        ];
    }
}
