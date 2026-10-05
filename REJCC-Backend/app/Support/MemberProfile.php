<?php

namespace App\Support;

use App\Models\MarketplaceListing;
use App\Models\User;

/**
 * Fiche complète d'un membre (profil « CV professionnel ») : utilisée par la
 * carte membre publique (/carte/{code}), la fiche détaillée de l'annuaire
 * (GET /members/{id}) et le trombinoscope des groupes sectoriels. Les
 * coordonnées (e-mail, téléphone) ne sont montrées aux membres connectés que
 * si la préférence « visibilite_profil » est active, et sur la page publique
 * que si le membre a activé « coordonnees_publiques » (désactivée par défaut).
 */
class MemberProfile
{
    /**
     * @param  bool  $public  true pour la page publique ouverte par le QR code
     *                        (accessible sans connexion) : les coordonnées n'y
     *                        figurent que si le membre l'a explicitement choisi.
     */
    public static function payload(User $user, bool $public = false): array
    {
        $prefs = $user->preferences ?? $user->defaultPreferences();
        $contactVisible = $public
            ? (bool) ($prefs['coordonnees_publiques'] ?? false)
            : (bool) ($prefs['visibilite_profil'] ?? true);

        $listings = MarketplaceListing::where('user_id', $user->id)
            ->where('statut', 'approuve')
            ->latest()
            ->limit(6)
            ->get(['type', 'title', 'category', 'description', 'price']);

        return [
            'id' => $user->id,
            'code' => $user->cardCode(),
            'numero' => $user->memberNumber(),
            'prenom' => $user->prenom,
            'nom' => $user->nom,
            'name' => $user->name,
            'photo' => $user->photo,
            'role' => $user->role,
            'role_label' => $user->roleLabel(),
            'ville' => $user->ville,
            'secteur' => $user->secteur,
            'is_active' => (bool) $user->is_active,
            'membre_depuis' => $user->created_at?->toDateString(),

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
        ];
    }
}
