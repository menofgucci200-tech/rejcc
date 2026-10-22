<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Http\Request;

/** Signalement d'un compte de test depuis une page d'erreur (sans Livewire). */
class RetourTesteurController extends Controller
{
    public function __invoke(Request $request)
    {
        Api::post('/suggestions', $request->only('categorie', 'message', 'page'), Api::token());

        return back()->with('retour_testeur', true);
    }
}
