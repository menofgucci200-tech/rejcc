<?php

namespace App\Http\Controllers;

use App\Support\Api;

/**
 * « Rejoindre la visio » : l'API inscrit le membre si besoin (session en
 * cours comprise), note son arrivée, puis on l'envoie sur la réunion (Google
 * Meet). Par une redirection serveur, le téléphone ne bloque pas l'ouverture.
 */
class RejoindreVisioController extends Controller
{
    public function __invoke(int $id)
    {
        $r = Api::post("/events/{$id}/rejoindre", [], Api::token());
        if (($r['ok'] ?? false) && ! empty($r['lien_visio'])) {
            return redirect()->away($r['lien_visio']);
        }

        return redirect()->route('espace-membre.evenements', ['evenement' => $id])
            ->with('rj_toast', ['message' => $r['message'] ?? 'La visio n\'est pas disponible pour le moment.', 'type' => 'erreur']);
    }
}
