<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationEvent extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'location', 'starts_at', 'capacity', 'is_open'];

    // Inscriptions ouvertes par défaut (aligné sur la valeur par défaut en base),
    // pour que le modèle fraîchement créé le reflète sans refresh().
    protected $attributes = ['is_open' => true];

    protected $casts = [
        'starts_at' => 'datetime',
        'capacity' => 'integer',
        'is_open' => 'boolean',
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

    /** Les inscriptions sont-elles ouvertes ? (ouvertes ET non pleines) */
    public function acceptsRegistrations(): bool
    {
        return $this->is_open && ! $this->isFull();
    }
}
