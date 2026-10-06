<?php

namespace App\Http\Controllers;

use App\Support\Api;

/** Vitrine publique des projets du réseau (porteurs ayant donné leur accord). */
class ProjetPublicController extends Controller
{
    public function index()
    {
        $projets = collect(Api::get('/public-projects')['projects'] ?? []);

        return view('pages.projets.index', [
            'projets' => $projets,
            'secteurs' => $projets->pluck('groupe.nom')->filter()->unique()->sort()->values(),
        ]);
    }

    public function show(int $id)
    {
        $result = Api::get("/public-projects/{$id}");
        if (! ($result['ok'] ?? false)) {
            abort(404);
        }
        $user = session('api_user');

        return view('pages.projets.show', [
            'projet' => $result['project'],
            'membreConnecte' => $user && ($user['role'] ?? null) !== 'admin',
        ]);
    }
}
