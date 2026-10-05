<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorApplication extends Model
{
    public const STATUTS = ['en_attente' => 'En cours d\'examen', 'acceptee' => 'Acceptée', 'refusee' => 'Non retenue'];

    protected $fillable = ['user_id', 'expertises', 'experience', 'motivation', 'disponibilites', 'statut', 'reponse', 'traite_par', 'traite_at'];

    protected function casts(): array
    {
        return ['expertises' => 'array', 'traite_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
