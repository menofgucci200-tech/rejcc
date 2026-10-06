<?php

namespace App\Http\Controllers;

use App\Support\Api;

/** Vitrine publique des offres d'emploi et de stage (postuler : réservé aux membres). */
class EmploiPublicController extends Controller
{
    public function index()
    {
        $offres = collect(Api::get('/public-opportunities')['opportunities'] ?? []);

        return view('pages.emplois.index', [
            'offres' => $offres,
            'types' => $offres->pluck('type_label', 'type')->unique()->sort(),
        ]);
    }

    public function show(int $id)
    {
        $result = Api::get("/public-opportunities/{$id}");
        if (! ($result['ok'] ?? false)) {
            abort(404);
        }
        $user = session('api_user');

        return view('pages.emplois.show', [
            'offre' => $result['opportunity'],
            'membreConnecte' => $user && ($user['role'] ?? null) !== 'admin',
        ]);
    }
}
