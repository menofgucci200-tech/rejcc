<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $fillable = [
        'mentor_id', 'mentore_id', 'statut', 'objectif', 'besoin', 'reponse', 'repondu_at', 'termine_at',
        'bilan', 'termine_par', 'note', 'avis', 'evalue_at', 'cree_par_admin',
    ];

    protected function casts(): array
    {
        return ['repondu_at' => 'datetime', 'termine_at' => 'datetime', 'evalue_at' => 'datetime', 'note' => 'integer', 'cree_par_admin' => 'boolean'];
    }

    /**
     * Statistiques publiques d'un mentor : membres accompagnés (mentorats en
     * cours ou terminés) et note moyenne laissée par ses mentorés.
     */
    public static function statsMentor(int $mentorId): array
    {
        $q = static::where('mentor_id', $mentorId)->whereIn('statut', ['accepte', 'termine']);
        $notes = (clone $q)->whereNotNull('note');

        return [
            'accompagnes' => (clone $q)->distinct()->count('mentore_id'),
            'note_moyenne' => $notes->count() ? round((float) $notes->avg('note'), 1) : null,
            'nb_avis' => $notes->count(),
        ];
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function mentore(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentore_id');
    }

    public function seances(): HasMany
    {
        return $this->hasMany(MentoringSession::class)->orderBy('debut_at');
    }

    public function participe(int $userId): bool
    {
        return $this->mentor_id === $userId || $this->mentore_id === $userId;
    }

    /** Demandes en attente ou mentorats en cours. */
    public function scopeOuverts(Builder $q): Builder
    {
        return $q->whereIn('statut', self::EN_COURS);
    }
}
