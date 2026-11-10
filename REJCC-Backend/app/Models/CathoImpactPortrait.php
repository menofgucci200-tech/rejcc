<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Carte de la galerie « Ils ont inspiré » : renvoie vers la publication sur un réseau social. */
class CathoImpactPortrait extends Model
{
    protected $table = 'cathoimpact_portraits';

    protected $fillable = ['nom', 'accroche', 'image_url', 'lien', 'reseau', 'candidature_id', 'ordre', 'visible'];

    protected function casts(): array
    {
        return ['visible' => 'boolean', 'ordre' => 'integer'];
    }

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(CathoImpactCandidature::class, 'candidature_id');
    }
}
