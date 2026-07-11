<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * Carte membre publique : le QR code de chaque membre pointe vers
 * /carte/{code} (code = identifiant sur 4 chiffres). La carte n'expose
 * que les informations d'identification, pas les données personnelles.
 */
class MemberCardController extends Controller
{
    public function show(string $code)
    {
        if (! preg_match('/^\d{1,6}$/', $code)) {
            return response()->json(['ok' => false, 'message' => 'Code invalide.'], 404);
        }

        $user = User::find((int) $code);

        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'Aucun membre pour ce code.'], 404);
        }

        return response()->json(['ok' => true, 'card' => [
            'code' => $user->cardCode(),
            'numero' => $user->memberNumber(),
            'prenom' => $user->prenom,
            'nom' => $user->nom,
            'name' => $user->name,
            'photo' => $user->photo,
            'role' => $user->role,               // brut, pour la variante de design
            'role_label' => $user->roleLabel(),  // libellé affiché
            'ville' => $user->ville,
            'secteur' => $user->secteur,
            'is_active' => (bool) $user->is_active,
            'membre_depuis' => $user->created_at?->toDateString(),
        ]]);
    }
}
