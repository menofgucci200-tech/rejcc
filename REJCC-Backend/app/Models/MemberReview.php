<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberReview extends Model
{
    protected $fillable = ['reviewer_id', 'reviewed_id', 'group_id', 'note', 'commentaire', 'masque', 'signale_at', 'signale_par', 'motif_signalement'];

    protected function casts(): array
    {
        return ['note' => 'integer', 'masque' => 'boolean', 'signale_at' => 'datetime'];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** Note moyenne et nombre d'avis visibles d'un membre. */
    public static function resume(int $userId): array
    {
        $q = static::where('reviewed_id', $userId)->where('masque', false);
        $nombre = (clone $q)->count();

        return ['moyenne' => $nombre ? round((float) (clone $q)->avg('note'), 1) : null, 'nombre' => $nombre];
    }
}
