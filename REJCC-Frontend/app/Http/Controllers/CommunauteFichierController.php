<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Support\Facades\Storage;

/**
 * Pièce jointe d'un message de la Communauté (photo, PDF, note vocale) :
 * l'API vérifie d'abord que le membre a accès au salon, puis le fichier privé
 * est servi (lecture des notes vocales par plages, pour pouvoir avancer).
 */
class CommunauteFichierController extends Controller
{
    public function __invoke(int $id)
    {
        $r = Api::get("/communaute/messages/{$id}/fichier", [], Api::token());
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
            'Content-Disposition' => 'inline; filename="'.$nom.'"; filename*=UTF-8\'\''.rawurlencode($nom),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
