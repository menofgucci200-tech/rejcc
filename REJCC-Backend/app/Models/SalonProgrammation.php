<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Annonce programmée : publiée automatiquement dans un salon à la date choisie. */
class SalonProgrammation extends Model
{
    protected $fillable = ['salon_id', 'user_id', 'body', 'publier_a', 'salon_message_id', 'publie_at', 'annule_at'];

    protected $casts = ['publier_a' => 'datetime', 'publie_at' => 'datetime', 'annule_at' => 'datetime'];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
