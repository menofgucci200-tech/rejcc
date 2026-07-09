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
            'is_active' => 'boolean',
        ];
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
