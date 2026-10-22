<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Formateur des sessions live : un membre du réseau (compte lié) ou un
 * intervenant extérieur (nom et e-mail). Pour chaque session, il reçoit un
 * lien personnel d'animateur.
 */
class Formateur extends Model
{
    protected $fillable = ['user_id', 'prenom', 'nom', 'email', 'telephone', 'specialite', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_formateur')->withPivot('jeton')->withTimestamps();
    }

    public function nomComplet(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function estExterieur(): bool
    {
        return $this->user_id === null;
    }
}
