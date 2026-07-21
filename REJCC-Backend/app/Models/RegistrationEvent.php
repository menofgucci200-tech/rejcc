<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationEvent extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'poster', 'fields', 'location', 'starts_at', 'registration_deadline', 'capacity', 'is_open'];

    // Inscriptions ouvertes par défaut (aligné sur la valeur par défaut en base),
    // pour que le modèle fraîchement créé le reflète sans refresh().
    protected $attributes = ['is_open' => true];

    protected $casts = [
        'starts_at' => 'datetime',
        'registration_deadline' => 'datetime',
        'capacity' => 'integer',
        'is_open' => 'boolean',
        'fields' => 'array',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    /** Places restantes (null si capacité illimitée). */
    public function remaining(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->participants()->count());
    }

    /** L'événement a-t-il atteint sa capacité ? */
    public function isFull(): bool
    {
        return $this->capacity !== null && $this->participants()->count() >= $this->capacity;
    }

    /** La date limite d'inscription est-elle dépassée ? */
    public function isPastDeadline(): bool
    {
        return $this->registration_deadline !== null && $this->registration_deadline->isPast();
    }

    /** Les inscriptions sont-elles ouvertes ? (ouvertes, non pleines, avant la date limite) */
    public function acceptsRegistrations(): bool
    {
        return $this->is_open && ! $this->isFull() && ! $this->isPastDeadline();
    }
}
