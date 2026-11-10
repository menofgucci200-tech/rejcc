<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Support\Facades\Storage;

/**
 * Photo jointe à une réponse CathoImpact, consultée par la Communication
 * (stockage privé partagé avec le site, voir SHARED_PRIVATE_ROOT).
 */
class CathoImpactPhotoController extends Controller
{
    /** Même contrôle que l'API : dossier cathoimpact/, nom aléatoire, extension d'image. */
    private const CHEMIN = '/^cathoimpact\/\d{4}\/\d{2}\/[0-9a-f-]{36}\.(jpg|jpeg|png|webp)$/';

    private const TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    public function __invoke(int $id)
    {
        $r = Api::get("/admin/cathoimpact/{$id}/photo", [], Api::token());
        if (! ($r['ok'] ?? false)) {
            abort(404, $r['message'] ?? 'Photo indisponible.');
        }
        $f = $r['fichier'];
        $disque = Storage::disk('local');
        if (! preg_match(self::CHEMIN, (string) $f['chemin']) || ! $disque->exists($f['chemin'])) {
            abort(404, 'Cette photo est introuvable.');
        }
        $nom = str_replace(['"', '\\', "\r", "\n"], '', $f['nom'] ?: basename($f['chemin']));
        $download = request()->boolean('telecharger');

        return response()->file($disque->path($f['chemin']), [
            // Type déduit de l'extension (déjà limitée par CHEMIN), jamais du contenu du fichier.
            'Content-Type' => self::TYPES[strtolower(pathinfo($f['chemin'], PATHINFO_EXTENSION))],
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$nom.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0',
        ]);
    }
}
