<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'bio',
        'preferences',
        'photo',
        'role',
        'permissions',
        'reference',
        'is_active',
    ];

    public function tokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
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
            'permissions' => 'array',
            'is_active' => 'boolean',
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

        return 'REJCC-'.$date->format('Y').'-'.$date->format('dm').$this->cardCode();
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

    public function defaultPreferences(): array
    {
        return [
            'notifications_email' => true,
            'rappels_quotidiens' => true,
            'visibilite_profil' => true,
            'newsletter' => true,
            'telechargement_hors_ligne' => false,
        ];
    }
}
