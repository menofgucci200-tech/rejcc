<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** État d'un membre dans un salon : lecture, notifications, rôle, sanctions. */
class SalonMembre extends Model
{
    public const NOTIFICATIONS = ['tout' => 'Tous les messages', 'mentions' => 'Mentions et réponses', 'rien' => 'Sourdine'];

    protected $fillable = ['salon_id', 'user_id', 'role', 'lu_jusqua', 'notifications', 'silence_jusqua', 'exclu_at', 'regles_acceptees_at', 'dernier_message_at'];

    protected $casts = [
        'lu_jusqua' => 'integer',
        'silence_jusqua' => 'datetime',
        'exclu_at' => 'datetime',
        'regles_acceptees_at' => 'datetime',
        'dernier_message_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estRenduMuet(): bool
    {
        return $this->silence_jusqua !== null && $this->silence_jusqua->isFuture();
    }
}
