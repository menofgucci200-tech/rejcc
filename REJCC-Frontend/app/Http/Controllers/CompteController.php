<?php

namespace App\Http\Controllers;

use App\Support\Api;

/** Actions du compte hors Livewire : lien de confirmation d'e-mail, export des données. */
class CompteController extends Controller
{
    /** Lien reçu par e-mail pour confirmer une nouvelle adresse (connecté ou non). */
    public function confirmerEmail(string $jeton)
    {
        $r = Api::post('/auth/email/confirmer', ['jeton' => $jeton]);
        $ok = (bool) ($r['ok'] ?? false);
        if ($ok && session('api_user')) {
            session(['api_user' => array_merge(session('api_user'), ['email' => $r['email']])]);
        }
        session()->flash('rj_toast', [
            'message' => $r['message'] ?? 'Ce lien n\'est plus valable.',
            'type' => $ok ? 'succes' : 'erreur',
        ]);

        return redirect(session('api_token') ? '/espace-membre/profil?onglet=securite' : '/connexion');
    }

    /** Téléchargement de toutes les données du compte (fichier JSON). */
    public function exporter()
    {
        $r = Api::get('/auth/export', [], Api::token());
        if (! ($r['ok'] ?? false)) {
            session()->flash('rj_toast', ['message' => $r['message'] ?? 'Export impossible pour le moment, réessayez plus tard.', 'type' => 'erreur']);

            return redirect('/espace-membre/profil?onglet=compte');
        }

        return response()->streamDownload(
            fn () => print(json_encode($r['donnees'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'mes-donnees-rejcc-'.now()->format('Y-m-d').'.json',
            ['Content-Type' => 'application/json; charset=utf-8', 'Cache-Control' => 'private, no-store'],
        );
    }
}
