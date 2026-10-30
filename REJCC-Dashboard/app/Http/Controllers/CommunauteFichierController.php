<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Support\Facades\Storage;

/**
 * Pièce jointe d'un message de la Communauté, consultée par l'équipe de
 * modération (stockage privé partagé avec le site, voir SHARED_PRIVATE_ROOT).
 */
class CommunauteFichierController extends Controller
{
    public function __invoke(int $id)
    {
        $r = Api::get("/admin/communaute/messages/{$id}/fichier", [], Api::token());
        if (! ($r['ok'] ?? false)) {
            abort(404, $r['message'] ?? 'Fichier indisponible.');
        }
        $f = $r['fichier'];
        $disque = Storage::disk('local');
        if (! str_starts_with((string) $f['chemin'], 'communaute/') || ! $disque->exists($f['chemin'])) {
            abort(404, 'Ce fichier est introuvable.');
        }
        $nom = str_replace(['"', '\\', "\r", "\n"], '', $f['nom'] ?: basename($f['chemin']));

        return response()->file($disque->path($f['chemin']), [
            'Content-Type' => $f['mime'] ?: $disque->mimeType($f['chemin']),
            'Content-Disposition' => 'inline; filename="'.$nom.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0',
        ]);
    }
}
