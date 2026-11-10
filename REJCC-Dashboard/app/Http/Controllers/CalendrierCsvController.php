<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Http\Request;

/**
 * Fichiers CSV du calendrier éditorial : modèle à remplir et export du mois
 * (préparés par l'API, rubrique « communication »).
 */
class CalendrierCsvController extends Controller
{
    public function __invoke(Request $request, string $quoi)
    {
        $r = $quoi === 'modele'
            ? Api::get('/admin/communication/calendrier/modele', [], Api::token())
            : Api::get('/admin/communication/calendrier/export', ['mois' => (string) $request->query('mois', '')], Api::token());
        abort_unless($r['ok'] ?? false, 403, 'Accès à la rubrique « Espace Communication » requis.');

        return response($r['csv'], 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.preg_replace('/[^a-z0-9.\-]/', '', $r['nom']).'"',
        ]);
    }
}
