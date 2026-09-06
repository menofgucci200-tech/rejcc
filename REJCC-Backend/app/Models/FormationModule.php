<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormationModule extends Model
{
    protected $fillable = ['formation_id', 'titre', 'description', 'video_url', 'document_url', 'duree', 'ordre'];

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(FormationModuleCompletion::class);
    }
}
