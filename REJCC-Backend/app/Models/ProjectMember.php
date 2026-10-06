<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Membre de l'équipe d'un projet (invitation, demande ou membre confirmé). */
class ProjectMember extends Model
{
    protected $fillable = ['project_id', 'user_id', 'role', 'statut', 'message'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
