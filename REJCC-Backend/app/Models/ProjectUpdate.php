<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Avancée publiée par l'équipe d'un projet (envoyée à ceux qui le suivent). */
class ProjectUpdate extends Model
{
    protected $fillable = ['project_id', 'user_id', 'body', 'image'];

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
