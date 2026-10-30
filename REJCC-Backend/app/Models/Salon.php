<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Salon de la Communauté REJCC (canal Annonces ou salon de discussion). */
class Salon extends Model
{
    public const TYPES = ['annonces' => 'Annonces', 'discussion' => 'Discussion'];

    public const ACCES = ['tous' => 'Tous les membres', 'abonnes' => 'Membres à jour de leur abonnement'];

    /** Mode lent proposé aux modérateurs (secondes => libellé). */
    public const MODES_LENTS = [0 => 'Désactivé', 30 => '30 secondes', 60 => '1 minute', 300 => '5 minutes', 900 => '15 minutes', 3600 => '1 heure'];

    protected $fillable = ['nom', 'slug', 'description', 'emoji', 'type', 'acces', 'lecture_seule', 'mode_lent_secondes', 'regles', 'ordre', 'archive_at'];

    protected $casts = [
        'lecture_seule' => 'boolean',
        'mode_lent_secondes' => 'integer',
        'ordre' => 'integer',
        'archive_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(SalonMessage::class);
    }

    public function membres(): HasMany
    {
        return $this->hasMany(SalonMembre::class);
    }

    public function estAnnonces(): bool
    {
        return $this->type === 'annonces';
    }

    /** Niveau de notification par défaut : tout pour les Annonces, les mentions ailleurs. */
    public function notificationsParDefaut(): string
    {
        return $this->estAnnonces() ? 'tout' : 'mentions';
    }
}
