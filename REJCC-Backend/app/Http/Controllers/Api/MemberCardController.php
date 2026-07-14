<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Models\User;

/**
 * Profil public du membre : le QR code de chaque carte pointe vers
 * /carte/{code} (code = identifiant sur 4 chiffres), qui présente le
 * membre comme une carte de visite / CV professionnel. Les coordonnées
 * (e-mail, téléphone) ne sont exposées que si le membre a laissé la
 * préférence « visibilité du profil » activée.
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

        $prefs = $user->preferences ?? $user->defaultPreferences();
        $contactVisible = (bool) ($prefs['visibilite_profil'] ?? true);

        // Offres publiées sur la Marketplace (déjà visibles des membres).
        $listings = MarketplaceListing::where('user_id', $user->id)
            ->where('statut', 'approuve')
            ->latest()
            ->limit(6)
            ->get(['type', 'title', 'category', 'description', 'price']);

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

            // Volet « CV professionnel »
            'profil' => $user->profil,
            'profil_label' => match ($user->profil) {
                'etudiant' => 'Étudiant & jeune diplômé',
                'porteur' => 'Porteur de projet',
                'entrepreneur' => 'Entrepreneur confirmé',
                default => null,
            },
            'organisation' => $user->organisation,
            'paroisse' => $user->paroisse,
            'bio' => $user->bio,
            'email' => $contactVisible ? $user->email : null,
            'telephone' => $contactVisible ? $user->telephone : null,
            'listings' => $listings,
        ]]);
    }
}
