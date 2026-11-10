<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Support\Facades\Storage;

/**
 * Document de la boîte à outils de marque (équipe Communication) : l'API
 * vérifie la rubrique « communication », puis le fichier privé est envoyé
 * en aperçu ou en téléchargement (?telecharger=1). Jamais public.
 */
class MarqueFichierController extends Controller
{
    /** Formats affichables dans le navigateur, et leur type. */
    private const APERCUS = [
        'pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp', 'gif' => 'image/gif',
    ];

    public function __invoke(int $id, ?string $nom = null)
    {
        $telecharger = request()->boolean('telecharger');
        $r = Api::get("/admin/communication/fichiers/{$id}/acces", $telecharger ? ['action' => 'telechargement'] : [], Api::token());
        if (! ($r['ok'] ?? false)) {
            abort(404, $r['message'] ?? 'Document indisponible.');
        }
        $f = $r['fichier'];
        if (! $f['chemin']) {
            // Gabarit Canva, dossier partagé… : simple lien externe.
            return str_starts_with((string) ($f['lien'] ?? ''), 'https://') ? redirect()->away($f['lien']) : abort(404, 'Ce document est introuvable.');
        }
        $disque = Storage::disk('local');
        if (! str_starts_with((string) $f['chemin'], 'marque/') || str_contains((string) $f['chemin'], '..') || ! $disque->exists($f['chemin'])) {
            abort(404, 'Ce fichier est introuvable.');
        }
        $nomFichier = str_replace(['"', '\\', '/', "\r", "\n"], '', $f['nom'] ?: basename($f['chemin']));
        // Aperçu « en ligne » seulement pour les images et les PDF, avec un type déduit
        // de l'extension : le type détecté à l'envoi (contenu du fichier) pourrait être
        // text/html pour un faux .png ou .txt, et exécuter du script. Le reste est téléchargé.
        $ext = strtolower(pathinfo($f['chemin'], PATHINFO_EXTENSION));
        $type = self::APERCUS[$ext] ?? null;
        $sur = $type !== null;
        $entetes = [
            'Content-Type' => $type ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0',
        ];

        return $telecharger || ! $sur
            ? $disque->download($f['chemin'], $nomFichier, $entetes)
            : $disque->response($f['chemin'], $nomFichier, $entetes, 'inline');
    }
}
