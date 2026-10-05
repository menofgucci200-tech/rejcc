<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentoringSession extends Model
{
    public const STATUTS = [
        'proposee' => 'À confirmer',
        'confirmee' => 'Confirmée',
        'annulee' => 'Annulée',
        'realisee' => 'Réalisée',
    ];

    protected $fillable = [
        'mentorship_id', 'propose_par', 'debut_at', 'duree_minutes', 'format', 'lieu',
        'ordre_du_jour', 'statut', 'motif_annulation', 'compte_rendu', 'prochaines_etapes',
    ];

    protected function casts(): array
    {
        return ['debut_at' => 'datetime', 'duree_minutes' => 'integer'];
    }

    public function mentorship(): BelongsTo
    {
        return $this->belongsTo(Mentorship::class);
    }
}
