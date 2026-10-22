<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Retour d'un compte de test : bug, suggestion, besoin du département… */
class Suggestion extends Model
{
    public const CATEGORIES = [
        'bug' => 'Bug ou erreur',
        'amelioration' => 'Amélioration',
        'besoin' => 'Besoin du département',
        'question' => 'Question',
    ];

    public const STATUTS = [
        'nouvelle' => 'Nouveau',
        'a_corriger' => 'À corriger',
        'corrige' => 'Corrigé',
        'a_etudier' => 'À étudier',
        'retenue' => 'Retenu',
        'ecartee' => 'Écarté',
    ];

    protected $fillable = ['user_id', 'departement', 'categorie', 'page', 'navigateur', 'message', 'statut', 'reponse'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
