<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sujet d'un grand salon (comme les « topics » de Telegram). */
class SalonSujet extends Model
{
    public const MAX_PAR_SALON = 30;

    protected $fillable = ['salon_id', 'titre', 'emoji', 'cree_par', 'ferme_at', 'dernier_message_at'];

    protected $casts = ['ferme_at' => 'datetime', 'dernier_message_at' => 'datetime'];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }
}
