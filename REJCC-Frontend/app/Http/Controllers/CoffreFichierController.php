<?php

namespace App\Http\Controllers;

use App\Support\Api;
use App\Support\Coffre;

/**
 * Sert un document personnel déchiffré : au membre lui-même, ou à l'équipe
 * REJCC si le membre l'a partagé (consultation tracée par l'API).
 */
class CoffreFichierController extends Controller
{
    public function membre(int $id, ?string $slug = null)
    {
        return $this->servir(Api::get("/mes-documents/{$id}/acces", [], Api::token()));
    }

    public function equipe(int $id, ?string $slug = null)
    {
        return $this->servir(Api::get("/admin/documents-personnels/{$id}/acces", [], Api::token()));
    }

    private function servir(array $r)
    {
        if (! ($r['ok'] ?? false)) {
            abort(404, $r['message'] ?? 'Document introuvable.');
        }
        $d = $r['document'];
        $contenu = Coffre::lire($d['fichier']);
        if ($contenu === null) {
            abort(404, 'Le fichier de ce document est introuvable.');
        }
        $nom = str_replace(['"', '\\', "\r", "\n"], '', $d['fichier_nom'] ?: 'document');
        $disposition = request()->boolean('telecharger') ? 'attachment' : 'inline';

        return response($contenu, 200, [
            'Content-Type' => $d['mime'] ?: 'application/octet-stream',
            'Content-Disposition' => $disposition.'; filename="'.$nom.'"; filename*=UTF-8\'\''.rawurlencode($nom),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
