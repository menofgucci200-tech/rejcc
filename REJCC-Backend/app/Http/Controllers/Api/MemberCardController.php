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

        if (! $user->reference) {
            $user->reference = 'REJCC-'.now()->format('Y').'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
            $user->save();
        }

        $roles = ['admin' => 'Administrateur', 'mentor' => 'Mentor', 'member' => 'Membre'];

        return response()->json([
            'ok' => true,
            'card' => [
                'code' => str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                'prenom' => $user->prenom,
                'nom' => $user->nom,
                'name' => $user->name,
                'photo' => $user->photo,
                'role' => $roles[$user->role] ?? 'Membre',
                'reference' => $user->reference,
                'ville' => $user->ville,
                'secteur' => $user->secteur,
                'is_active' => (bool) $user->is_active,
                'membre_depuis' => $user->created_at?->toDateString(),
            ],
        ]);
    }
}
