<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Message d'un salon de la Communauté (texte, photo, document ou note vocale). */
class SalonMessage extends Model
{
    /** Délai pendant lequel l'auteur peut modifier son message. */
    public const MINUTES_MODIFICATION = 15;

    protected $fillable = ['salon_id', 'user_id', 'type', 'body', 'reponse_a_id', 'mentions', 'mention_tous', 'fichier', 'fichier_nom', 'fichier_mime', 'fichier_taille', 'duree', 'carte_type', 'carte_id', 'epingle_at', 'epingle_par', 'edite_at', 'supprime_at', 'supprime_par'];

    /** Nombre maximal de messages épinglés dans un salon. */
    public const EPINGLES_MAX = 5;

    protected $casts = [
        'mentions' => 'array',
        'mention_tous' => 'boolean',
        'fichier_taille' => 'integer',
        'duree' => 'integer',
        'carte_id' => 'integer',
        'epingle_at' => 'datetime',
        'edite_at' => 'datetime',
        'supprime_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reponseA(): BelongsTo
    {
        return $this->belongsTo(SalonMessage::class, 'reponse_a_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(SalonReaction::class);
    }

    public function sondage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SalonSondage::class);
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(SalonSignalement::class);
    }

    public function estSupprime(): bool
    {
        return $this->supprime_at !== null;
    }
}
