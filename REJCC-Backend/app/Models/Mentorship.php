<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mentorship extends Model
{
    public const EN_COURS = ['en_attente', 'accepte'];

    public const STATUTS = [
        'en_attente' => 'En attente de réponse',
        'accepte' => 'En cours',
        'refuse' => 'Non retenue',
        'annule' => 'Annulée',
        'termine' => 'Terminé',
    ];

    protected $fillable = ['mentor_id', 'mentore_id', 'statut', 'objectif', 'besoin', 'reponse', 'repondu_at', 'termine_at'];

    protected function casts(): array
    {
        return ['repondu_at' => 'datetime', 'termine_at' => 'datetime'];
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function mentore(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentore_id');
    }

    /** Demandes en attente ou mentorats en cours. */
    public function scopeOuverts(Builder $q): Builder
    {
        return $q->whereIn('statut', self::EN_COURS);
    }
}
