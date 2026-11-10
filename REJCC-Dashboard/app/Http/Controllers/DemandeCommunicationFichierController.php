<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Support\Facades\Storage;

/**
 * Pièce jointe d'une demande de communication, consultée par le demandeur ou
 * par la Communication : l'API vérifie l'accès, puis le fichier privé
 * (stockage partagé avec le site, voir SHARED_PRIVATE_ROOT) est envoyé.
 */
class DemandeCommunicationFichierController extends Controller
{
    /** Même contrôle que l'API : dossier dédié, nom aléatoire, image ou PDF. */
    private const CHEMIN = '/^demandes-communication\/\d{4}\/\d{2}\/[0-9a-f-]{36}\.(jpg|jpeg|png|webp|pdf)$/';

    private const TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];

    public function __invoke(int $id, int $n)
    {
        $r = Api::get("/admin/demandes-communication/{$id}/fichiers/{$n}", [], Api::token());
        if (! ($r['ok'] ?? false)) {
            abort(($r['message'] ?? '') === 'Cette pièce jointe ne vous est pas accessible.' ? 403 : 404, $r['message'] ?? 'Pièce jointe indisponible.');
        }
        $f = $r['fichier'];
        $disque = Storage::disk('local');
        if (! preg_match(self::CHEMIN, (string) $f['chemin']) || ! $disque->exists($f['chemin'])) {
            abort(404, 'Ce fichier est introuvable.');
        }
        $nom = str_replace(['"', '\\', '/', "\r", "\n"], '', $f['nom'] ?: basename($f['chemin']));

        return response()->file($disque->path($f['chemin']), [
            // Type déduit de l'extension (déjà limitée par CHEMIN), jamais du contenu du fichier.
            'Content-Type' => self::TYPES[strtolower(pathinfo($f['chemin'], PATHINFO_EXTENSION))],
            'Content-Disposition' => (request()->boolean('telecharger') ? 'attachment' : 'inline').'; filename="'.$nom.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0',
        ]);
    }
}
