<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MemberProfile;

/**
 * Profil public du membre : le QR code de chaque carte pointe vers
 * /carte/{code} (code = identifiant sur 4 chiffres), qui présente le
 * membre comme une carte de visite / CV professionnel.
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

        // La carte (preuve d'adhésion) n'est délivrée qu'aux membres à jour
        // de leur abonnement annuel.
        if (! $user->hasActiveSubscription()) {
            return response()->json(['ok' => true, 'card' => [
                'code' => $user->cardCode(),
                'locked' => true,
                'prenom' => $user->prenom,
                'nom' => $user->nom,
            ]]);
        }

        return response()->json(['ok' => true, 'card' => MemberProfile::payload($user)]);
    }
}
