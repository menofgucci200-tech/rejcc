<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ligne du journal de modération de la Communauté. */
class SalonModeration extends Model
{
    public const ACTIONS = [
        'message_supprime' => 'Message supprimé',
        'silence' => 'Membre rendu muet',
        'silence_leve' => 'Silence levé',
        'exclusion' => 'Membre exclu du salon',
        'reintegration' => 'Membre réintégré',
        'lecture_seule' => 'Lecture seule',
        'mode_lent' => 'Mode lent',
        'moderateur_nomme' => 'Référent nommé',
        'moderateur_retire' => 'Référent retiré',
        'salon_cree' => 'Salon créé',
        'salon_modifie' => 'Salon modifié',
        'salon_archive' => 'Salon archivé',
        'salon_restaure' => 'Salon restauré',
        'signalement_classe' => 'Signalement classé',
        'mots_interdits' => 'Mots interdits modifiés',
    ];

    protected $fillable = ['salon_id', 'acteur_id', 'cible_id', 'salon_message_id', 'action', 'detail'];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function acteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acteur_id');
    }

    public function cible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cible_id');
    }
}
