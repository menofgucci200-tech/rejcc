<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Publication prévue au calendrier éditorial de la Communication. */
class CommunicationPublication extends Model
{
    protected $table = 'communication_publications';

    protected $fillable = [
        'date', 'heure', 'titre', 'type', 'format', 'canaux', 'responsable_id', 'responsable', 'notes',
        'statut', 'liens', 'publie_at', 'publie_par', 'demande_id', 'rappel_veille_at', 'rappel_jour_at',
        'retard_signale_at', 'cree_par',
    ];

    protected $casts = [
        'date' => 'date',
        'canaux' => 'array',
        'liens' => 'array',
        'publie_at' => 'datetime',
        'rappel_veille_at' => 'datetime',
        'rappel_jour_at' => 'datetime',
        'retard_signale_at' => 'datetime',
        'demande_id' => 'integer',
    ];

    /** Statuts qui attendent encore une publication. */
    public const EN_ATTENTE = ['prevu', 'pret'];

    /** Date enregistrée en « AAAA-MM-JJ » (comparaisons fiables sur SQLite comme sur MySQL). */
    public function setDateAttribute($valeur): void
    {
        $this->attributes['date'] = \Illuminate\Support\Carbon::parse($valeur)->toDateString();
    }

    public function responsableCompte(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /** En retard : date passée, ni publiée ni annulée. */
    public function enRetard(): bool
    {
        return $this->date->lt(today()) && ! in_array($this->statut, ['publie', 'annule'], true);
    }

    /** Publications en retard (requête). */
    public function scopeRetards($q)
    {
        return $q->where('date', '<', today()->toDateString())->whereNotIn('statut', ['publie', 'annule']);
    }
}
