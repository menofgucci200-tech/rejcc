<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Support\Facades\Storage;

/**
 * Sert un document de la bibliothèque : l'API vérifie d'abord les droits du
 * membre (accès par document), puis le fichier privé est envoyé (aperçu
 * intégré ou téléchargement). Les fichiers ne sont jamais publics.
 */
class DocumentFichierController extends Controller
{
    public function __invoke(int $id, ?string $slug = null)
    {
        $telecharger = request()->boolean('telecharger');
        $r = Api::get("/documents/{$id}/acces", ['action' => $telecharger ? 'telechargement' : 'vue'], Api::token());
        if (! ($r['ok'] ?? false)) {
            abort(($r['code'] ?? null) === 'acces_refuse' ? 403 : 404, $r['message'] ?? 'Document indisponible.');
        }
        $d = $r['document'];
        if (! $d['fichier']) {
            return $d['url'] && str_starts_with($d['url'], 'http') ? redirect()->away($d['url']) : abort(404, 'Ce document sera bientôt disponible.');
        }
        if (! Storage::disk('local')->exists($d['fichier'])) {
            abort(404, 'Le fichier de ce document est introuvable.');
        }
        $nom = $d['fichier_nom'] ?: basename($d['fichier']);
        $entetes = ['Content-Type' => $d['mime'] ?: Storage::disk('local')->mimeType($d['fichier']), 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=0'];

        return $telecharger
            ? Storage::disk('local')->download($d['fichier'], $nom, $entetes)
            : Storage::disk('local')->response($d['fichier'], $nom, $entetes, 'inline');
    }
}
