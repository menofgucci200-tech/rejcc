<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Signalement d'un message de salon à l'équipe de modération. */
class SalonSignalement extends Model
{
    protected $fillable = ['salon_message_id', 'user_id', 'motif', 'statut', 'decision', 'traite_par', 'traite_at'];

    protected $casts = ['traite_at' => 'datetime'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(SalonMessage::class, 'salon_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
