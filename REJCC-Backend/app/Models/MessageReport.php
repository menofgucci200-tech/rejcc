<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Conversation signalée par l'un de ses participants. */
class MessageReport extends Model
{
    protected $fillable = ['reporter_id', 'reported_id', 'motif', 'statut', 'decision', 'traite_par', 'traite_at'];

    protected function casts(): array
    {
        return ['traite_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reported(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_id');
    }
}
