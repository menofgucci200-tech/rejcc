<?php

namespace App\Http\Controllers;

use App\Support\Api;

/**
 * Reçu PDF d'un paiement d'abonnement, relayé depuis l'API : pour le payeur
 * ou le bénéficiaire, ou pour l'équipe (section Membres).
 */
class RecuController extends Controller
{
    public function membre(string $ref)
    {
        return $this->relayer(Api::brut('/subscription/recus/'.rawurlencode($ref), Api::token()));
    }

    public function admin(string $ref)
    {
        return $this->relayer(Api::brut('/admin/abonnements/recus/'.rawurlencode($ref), Api::token()));
    }

    private function relayer(\Illuminate\Http\Client\Response $r)
    {
        if (! $r->successful() || ! str_starts_with((string) $r->header('Content-Type'), 'application/pdf')) {
            abort(404, 'Ce reçu est introuvable.');
        }
        $nom = preg_replace('/[^A-Za-z0-9\-]/', '', (string) str($r->header('Content-Disposition'))->after('filename="')->before('.pdf')) ?: 'recu-rejcc';

        return response($r->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => (request()->boolean('telecharger') ? 'attachment' : 'inline').'; filename="'.$nom.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
