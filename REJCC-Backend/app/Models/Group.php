<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'ordre'];

    /** Colonnes de la fiche professionnelle du membre dans le groupe. */
    public const FICHE = ['specialite', 'services', 'zone', 'disponibilites', 'telephone_visible'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot(self::FICHE)->withTimestamps();
    }
}
