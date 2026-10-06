<?php

namespace App\Http\Controllers;

use App\Support\Api;

/**
 * PDF officiel d'un certificat, relayé depuis l'API : pour son titulaire,
 * pour l'équipe, ou pour toute personne disposant du code de vérification
 * (copie officielle de comparaison). Le fichier n'est jamais modifié ici.
 */
class CertificatPdfController extends Controller
{
    public function membre(int $id)
    {
        return $this->relayer(Api::brut("/my-certificates/{$id}/pdf", Api::token()));
    }

    public function admin(int $id)
    {
        return $this->relayer(Api::brut("/admin/certificates/{$id}/pdf", Api::token()));
    }

    public function apercu()
    {
        return $this->relayer(Api::brut('/admin/certificats/apercu', Api::token()));
    }

    public function public(string $code)
    {
        return $this->relayer(Api::brut('/certificats/verifier/'.rawurlencode($code).'/pdf', null, [
            'X-Visiteur' => hash('sha256', request()->ip().'|'.config('app.key')),
        ]));
    }

    private function relayer(\Illuminate\Http\Client\Response $r)
    {
        if ($r->status() === 429) {
            abort(429);
        }
        if (! $r->successful() || ! str_starts_with((string) $r->header('Content-Type'), 'application/pdf')) {
            abort(404, 'Ce certificat est introuvable ou n\'est plus valide.');
        }
        $nom = preg_replace('/[^A-Za-z0-9\-]/', '', (string) str($r->header('Content-Disposition'))->after('filename="')->before('.pdf')) ?: 'certificat-rejcc';

        return response($r->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => (request()->boolean('telecharger') ? 'attachment' : 'inline').'; filename="'.$nom.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
