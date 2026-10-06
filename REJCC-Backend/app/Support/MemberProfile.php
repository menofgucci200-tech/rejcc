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
    public const FORMATS_MENTORAT = [
        'visio' => 'En visio',
        'presentiel' => 'En présentiel',
        'les_deux' => 'Visio ou présentiel',
    ];

    /** Profil de mentor (expertises, présentation, disponibilités, format, capacité). */
    public static function mentor(User $user): array
    {
        return [
            'expertises' => $user->mentor_expertises ?? [],
            'bio' => $user->mentor_bio,
            'disponibilites' => $user->mentor_disponibilites,
            'format' => $user->mentor_format,
            'format_label' => static::FORMATS_MENTORAT[$user->mentor_format] ?? null,
            'capacite' => (int) $user->mentor_capacite,
            'accepte' => (bool) $user->mentor_accepte,
            'stats' => \App\Models\Mentorship::statsMentor($user->id),
        ];
    }

    public static function payload(User $user, bool $public = false): array
    {
        $prefs = $user->preferencesEffectives();
        $contactVisible = $public
            ? (bool) ($prefs['coordonnees_publiques'] ?? false)
            : (bool) ($prefs['visibilite_profil'] ?? false);

        // Certificats valides du registre ; sur la page publique, seulement ceux que le membre a choisi d'afficher.
        $certificats = \App\Models\Certificate::where('user_id', $user->id)->where('statut', 'valide')
            ->when($public, fn ($q) => $q->where('visible_bio', true))
            ->orderByDesc('delivre_le')
            ->get()
            ->map(fn (\App\Models\Certificate $c) => [
                'titre' => $c->titre,
                'categorie' => \App\Models\Certificate::TYPES[$c->type] ?? '',
                'type' => $c->type,
                'reference' => $c->reference,
                'obtenu_le' => $c->delivre_le->toDateString(),
                'url_verification' => $c->urlVerification(false),
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
            'mentor' => $user->role === 'mentor' ? static::mentor($user) : null,
            'avis' => \App\Models\MemberReview::resume($user->id),
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
            // Projets validés qu'il porte ou dont il fait partie de l'équipe.
            'projets' => Project::where('statut', 'valide')
                ->where(fn ($w) => $w->where('user_id', $user->id)
                    ->orWhereHas('equipe', fn ($e) => $e->where('user_id', $user->id)->where('statut', 'membre')))
                ->with(['equipe' => fn ($e) => $e->where('user_id', $user->id)])
                ->latest()->limit(6)->get()
                ->map(fn (Project $p) => [
                    'id' => $p->id, 'title' => $p->title, 'description' => $p->accroche ?: $p->description,
                    'status' => Project::STADES[$p->stade] ?? 'Validé',
                    'role' => $p->user_id === $user->id ? 'Porteur du projet' : ($p->equipe->first()?->role ?: "Membre de l'équipe"),
                ])->values(),
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
