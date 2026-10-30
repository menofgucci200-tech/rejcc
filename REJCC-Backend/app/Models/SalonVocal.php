<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Discussion vocale en direct lancée dans un salon (réunion Meet ou lien de visio). */
class SalonVocal extends Model
{
    /** Une discussion vocale se termine d'elle-même après cette durée. */
    public const DUREE_MAX_HEURES = 3;

    protected $table = 'salon_vocaux';

    protected $fillable = ['salon_id', 'salon_message_id', 'lance_par', 'titre', 'lien', 'fournisseur', 'google_event_id', 'debut_at', 'fin_at', 'termine_par'];

    protected $casts = ['debut_at' => 'datetime', 'fin_at' => 'datetime'];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function lanceur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lance_par');
    }

    public function enCours(): bool
    {
        return $this->fin_at === null && $this->debut_at->copy()->addHours(self::DUREE_MAX_HEURES)->isFuture();
    }

    /** Fin effective (déclarée, ou automatique après la durée maximale). */
    public function finEffective(): ?\Illuminate\Support\Carbon
    {
        return $this->fin_at ?? ($this->enCours() ? null : $this->debut_at->copy()->addHours(self::DUREE_MAX_HEURES));
    }
}
