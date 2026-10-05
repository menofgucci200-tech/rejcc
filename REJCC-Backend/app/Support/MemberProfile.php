<?php

namespace App\Support;

use App\Models\EventRegistration;
use App\Models\FormationEnrollment;
use App\Models\MarketplaceListing;
use App\Models\PathBadge;
use App\Models\Project;
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

        $certificats = FormationEnrollment::with('formation:id,title,category,is_certifying')
            ->where('user_id', $user->id)
            ->certificats()
            ->orderByDesc('completed_at')
            ->get()
            ->map(fn (FormationEnrollment $e) => [
                'titre' => $e->formation->title,
                'categorie' => $e->formation->category,
                'reference' => $e->certificateReference(),
                'obtenu_le' => $e->completed_at->toDateString(),
            ])->values();

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
            // Abonnement annuel, renouvelable à date anniversaire.
            'a_jour' => $user->hasActiveSubscription(),
            'abonnements_obligatoires' => SubscriptionMode::enforced(),
            'valable_jusqu' => $user->subscription_expires_at?->toDateString(),

            'profil' => $user->profil,
            'profil_label' => match ($user->profil) {
                'etudiant' => 'Étudiant & jeune diplômé',
                'porteur' => 'Porteur de projet',
                'entrepreneur' => 'Entrepreneur confirmé',
                default => null,
            },
            'organisation' => $user->organisation,
            'titre' => $user->titre,
            'paroisse' => $user->paroisse,
            'diocese' => $user->diocese,
            'bio' => $user->bio,
            'competences' => $user->competences ?? [],
            'parcours' => $user->parcours ?? [],
            'liens' => $user->liens ?? [],

            // Données vérifiées par la plateforme (activité réelle dans le réseau)
            'certificats' => $certificats,
            'badges_parcours' => PathBadge::with('path:id,title,badge_icon,badge_couleur')
                ->where('user_id', $user->id)
                ->orderByDesc('obtenu_at')
                ->get()
                ->filter(fn (PathBadge $b) => $b->path)
                ->map(fn (PathBadge $b) => [
                    'titre' => $b->path->title,
                    'icon' => $b->path->badge_icon,
                    'couleur' => $b->path->badge_couleur,
                    'obtenu_le' => $b->obtenu_at->toDateString(),
                ])->values(),
            'groupes' => $user->groups()->orderBy('ordre')->get(['groups.name'])
                ->map(fn ($g) => ['nom' => $g->name, 'specialite' => $g->pivot->specialite])->values(),
            'projets' => Project::where('user_id', $user->id)
                ->whereNotIn('status', ['En évaluation', 'Refusé'])
                ->latest()->limit(4)->get(['title', 'description', 'status']),
            'engagement' => [
                'formations_terminees' => FormationEnrollment::where('user_id', $user->id)->whereNotNull('completed_at')->count(),
                'evenements' => EventRegistration::where('user_id', $user->id)->count(),
                'certificats' => $certificats->count(),
            ],
            'email' => $contactVisible ? $user->email : null,
            'telephone' => $contactVisible ? $user->telephone : null,
            'listings' => $listings,
        ];
    }
}
