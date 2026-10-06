<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'excerpt', 'body', 'location', 'en_ligne', 'lien_visio', 'category', 'statut', 'motif_annulation',
        'starts_at', 'ends_at', 'time_label', 'image', 'capacity', 'reserve_abonnes', 'inscriptions_ouvertes', 'date_limite',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'date_limite' => 'datetime',
        'body' => 'array',
        'en_ligne' => 'boolean',
        'reserve_abonnes' => 'boolean',
        'inscriptions_ouvertes' => 'boolean',
        'capacity' => 'integer',
    ];

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    /** Événements visibles par les membres (publiés ou annulés, pas les brouillons). */
    public function scopeVisibles($query)
    {
        return $query->whereIn('statut', ['publie', 'annule']);
    }

    /** Nombre de personnes inscrites (toutes inscriptions confondues). */
    public function nbInscrits(): int
    {
        return $this->registrations()->count();
    }

    public function placesRestantes(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->nbInscrits());
    }

    public function estPasse(): bool
    {
        return ($this->ends_at ?? $this->starts_at)->isPast();
    }

    /**
     * Raison pour laquelle ce membre ne peut pas s'inscrire (null s'il le peut).
     */
    public function raisonRefus(User $user): ?string
    {
        return match (true) {
            $this->statut === 'annule' => 'Cet événement est annulé.',
            $this->statut !== 'publie' => "Cet événement n'est pas encore publié.",
            $this->starts_at->isPast() => 'Cet événement a déjà eu lieu.',
            ! $this->inscriptions_ouvertes => 'Les inscriptions sont fermées.',
            $this->date_limite !== null && $this->date_limite->isPast() => "La date limite d'inscription est dépassée.",
            $this->reserve_abonnes && ! $user->hasActiveSubscription() => 'Cet événement est réservé aux membres à jour de leur abonnement annuel.',
            $this->placesRestantes() === 0 => 'Toutes les places ont été réservées : l\'événement est complet.',
            default => null,
        };
    }
}
