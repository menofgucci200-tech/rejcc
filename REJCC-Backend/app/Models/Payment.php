<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUTS = ['pending' => 'En attente', 'success' => 'Payé', 'failed' => 'Échoué', 'abandonne' => 'Abandonné'];

    protected $fillable = [
        'member_id', 'user_id', 'beneficiaire_id', 'reference', 'provider', 'moyen', 'type', 'amount', 'currency',
        'status', 'transaction_id', 'paye_at', 'periode_debut', 'periode_fin', 'recu_numero', 'recu_fichier', 'verifie_at',
    ];

    protected $casts = [
        'paye_at' => 'datetime', 'periode_debut' => 'date', 'periode_fin' => 'date', 'verifie_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** Le payeur. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Le membre abonné (lui-même, ou un autre membre s'il s'agit d'un abonnement offert). */
    public function beneficiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiaire_id');
    }

    public function estOffert(): bool
    {
        return $this->beneficiaire_id !== null && $this->beneficiaire_id !== $this->user_id;
    }
}
