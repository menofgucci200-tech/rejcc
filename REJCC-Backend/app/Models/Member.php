<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'reference', 'profil', 'prenom', 'nom', 'email', 'telephone',
        'genre', 'ville', 'secteur', 'organisation', 'message',
        'paiement', 'statut',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
