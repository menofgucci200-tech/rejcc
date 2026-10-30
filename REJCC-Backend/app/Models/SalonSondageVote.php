<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Vote d'un membre pour un choix de sondage. */
class SalonSondageVote extends Model
{
    protected $fillable = ['salon_sondage_id', 'salon_sondage_option_id', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
