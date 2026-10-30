<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Sondage publié dans un salon de la Communauté. */
class SalonSondage extends Model
{
    public const OPTIONS_MIN = 2;

    public const OPTIONS_MAX = 10;

    protected $fillable = ['salon_message_id', 'question', 'choix_multiple', 'anonyme', 'fin_at', 'clos_at'];

    protected $casts = [
        'choix_multiple' => 'boolean',
        'anonyme' => 'boolean',
        'fin_at' => 'datetime',
        'clos_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(SalonMessage::class, 'salon_message_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(SalonSondageOption::class)->orderBy('ordre')->orderBy('id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SalonSondageVote::class);
    }

    public function estClos(): bool
    {
        return $this->clos_at !== null || ($this->fin_at !== null && $this->fin_at->isPast());
    }
}
