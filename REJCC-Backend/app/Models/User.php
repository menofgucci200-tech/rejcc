<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'prenom',
        'nom',
        'email',
        'telephone',
        'password',
        'genre',
        'ville',
        'date_naissance',
        'paroisse',
        'secteur',
        'profil',
        'organisation',
        'titre',
        'diocese',
        'bio',
        'competences',
        'parcours',
        'liens',
        'preferences',
        'photo',
        'piece_identite',
        'role',
        'permissions',
        'reference',
        'is_active',
        'subscription_expires_at',
        'mentor_expertises',
        'mentor_bio',
        'mentor_disponibilites',
        'mentor_format',
        'mentor_capacite',
        'mentor_accepte',
        'conditions_acceptees_at',
        'versions_acceptees',
    ];

    public function tokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    /** Groupes sectoriels rejoints (adhésion multiple libre). */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)->withPivot(Group::FICHE)->withTimestamps();
    }

    /** Historique des paiements (adhésion + abonnements annuels). */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Accès aux fonctionnalités premium : toujours accordé quand les abonnements
     * ne sont pas obligatoires (interrupteur du tableau de bord admin), sinon
     * réservé aux abonnés à jour.
     */
    public function hasActiveSubscription(): bool
    {
        return ! \App\Support\SubscriptionMode::enforced() || $this->hasPaidSubscription();
    }

    /**
     * Abonnement annuel (10 000 F) réellement payé et en cours. Les
     * administrateurs et les mentors en sont exemptés : les mentors donnent
     * de leur temps au réseau et doivent pouvoir échanger avec leurs mentorés.
     */
    public function hasPaidSubscription(): bool
    {
        return $this->isExemptFromSubscription()
            || ($this->subscription_expires_at !== null && $this->subscription_expires_at->isFuture());
    }

    public function isExemptFromSubscription(): bool
    {
        return in_array($this->role, ['admin', 'mentor'], true);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_naissance' => 'date',
            'preferences' => 'array',
            'competences' => 'array',
            'parcours' => 'array',
            'liens' => 'array',
            'permissions' => 'array',
            'is_active' => 'boolean',
            'subscription_expires_at' => 'datetime',
            'mentor_expertises' => 'array',
            'mentor_capacite' => 'integer',
            'mentor_accepte' => 'boolean',
            'conditions_acceptees_at' => 'datetime',
            'versions_acceptees' => 'array',
        ];
    }

    /**
     * Numéro de membre officiel : REJCC-{année}-{jour}{mois}{code}.
     * Ex. inscrit le 19/06/2026, code 2000 => REJCC-2026-19062000.
     * Le code (4 chiffres) est celui de la carte, cible du QR (/carte/{code}).
     */
    public function memberNumber(): string
    {
        $date = $this->created_at ?? now();

        // Mêmes chiffres qu'avant, regroupés pour la lecture : REJCC-2026-0410-0006
        // (année d'adhésion, jour+mois, code carte).
        return 'REJCC-'.$date->format('Y').'-'.$date->format('dm').'-'.$this->cardCode();
    }

    /** Code carte à 4 chiffres généré à l'inscription (identifiant, cible du QR). */
    public function cardCode(): string
    {
        return str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Libellé du statut affiché sur la carte. */
    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Administrateur',
            'mentor' => 'Mentor',
            default => 'Membre officiel',
        };
    }

    /** Préférences enregistrées, complétées par les valeurs par défaut des réglages ajoutés depuis. */
    public function preferencesEffectives(): array
    {
        return array_merge($this->defaultPreferences(), $this->preferences ?? []);
    }

    public function defaultPreferences(): array
    {
        return [
            'notifications_email' => true,
            'rappels_quotidiens' => true,
            // Présence dans l'annuaire des membres (désactivable).
            'apparaitre_annuaire' => true,
            // Téléphone et e-mail montrés aux membres : sur choix explicite.
            'visibilite_profil' => false,
            // Coordonnées sur la page publique du QR code : uniquement sur choix explicite.
            'coordonnees_publiques' => false,
            'newsletter' => true,
            'telechargement_hors_ligne' => false,
        ];
    }
}
