<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Candidature d'un membre à une offre d'emploi ou de stage. */
class OpportunityApplication extends Model
{
    public const STATUTS = [
        'recue' => 'Reçue',
        'preselection' => 'Présélectionnée',
        'retenue' => 'Retenue',
        'non_retenue' => 'Non retenue',
        'retiree' => 'Retirée',
    ];

    protected $fillable = ['opportunity_id', 'user_id', 'message', 'cv_url', 'cv_name', 'statut', 'note', 'vue_at'];

    protected $casts = ['vue_at' => 'datetime'];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
