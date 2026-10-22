<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\GoogleMeet;
use Illuminate\Http\Request;
use Throwable;

/**
 * Connexion du compte Google du REJCC (réunions Meet des sessions live) :
 * autorisation chez Google, sans mot de passe sur la plateforme.
 */
class GoogleMeetController extends Controller
{
    public function etat()
    {
        return response()->json(['ok' => true, 'configure' => GoogleMeet::estConfigure(), 'compte' => GoogleMeet::compte()]);
    }

    /** POST /admin/google-meet/connexion : adresse de la page d'autorisation Google. */
    public function connexion(Request $request)
    {
        if (! GoogleMeet::estConfigure()) {
            return response()->json(['ok' => false, 'message' => 'La connexion Google n\'est pas encore configurée sur le serveur (identifiants OAuth).'], 422);
        }
        $dashboard = rtrim((string) config('app.dashboard_url'), '/');
        $retour = (string) $request->input('retour', $dashboard.'/admin/formations-live');
        if (! str_starts_with($retour, $dashboard.'/')) {
            $retour = $dashboard.'/admin/formations-live';
        }

        return response()->json(['ok' => true, 'url' => GoogleMeet::urlAutorisation($retour)]);
    }

    public function deconnexion()
    {
        GoogleMeet::deconnecter();

        return response()->json(['ok' => true]);
    }

    /** GET /google/callback : retour de Google, puis retour au dashboard. */
    public function callback(Request $request)
    {
        $dashboard = rtrim((string) config('app.dashboard_url'), '/');
        if ($request->filled('error') || ! $request->filled('code') || ! $request->filled('state')) {
            return redirect()->away($dashboard.'/admin/formations-live?google=refuse');
        }
        try {
            $retour = GoogleMeet::terminerConnexion((string) $request->query('code'), (string) $request->query('state'));
        } catch (Throwable $e) {
            report($e);

            return redirect()->away($dashboard.'/admin/formations-live?google=erreur');
        }

        return redirect()->away((str_starts_with($retour, $dashboard.'/') ? $retour : $dashboard.'/admin/formations-live').'?google=connecte');
    }
}
