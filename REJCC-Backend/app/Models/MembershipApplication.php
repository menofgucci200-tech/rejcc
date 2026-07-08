<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipApplication extends Model
{
    protected $fillable = [
        'nom_prenoms',
        'sexe',
        'tranche_age',
        'whatsapp',
        'email',
        'ville',
        'password',
        'connotation_religieuse',
        'paroisse',
        'statut_actuel',
        'niveau_etudes',
        'domaines_formation',
        'competences',
        'description_competences',
        'a_activite',
        'nom_activite',
        'secteurs_activite',
        'anciennete',
        'domaines_futurs',
        'attentes',
        'formations_interet',
        'defi_principal',
        'revenu_mensuel',
        'traite',
        'statut',
        'user_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'statut_actuel' => 'array',
        'competences' => 'array',
        'secteurs_activite' => 'array',
        'domaines_futurs' => 'array',
        'attentes' => 'array',
        'formations_interet' => 'array',
        'traite' => 'boolean',
        'password' => 'hashed',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
