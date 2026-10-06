<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryAlbum extends Model
{
    protected $fillable = ['titre', 'slug', 'date_evenement', 'lieu', 'description', 'couverture', 'publie', 'ordre'];

    protected $casts = [
        'date_evenement' => 'date:Y-m-d',
        'publie' => 'boolean',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class, 'album_id')->orderBy('ordre')->orderBy('id');
    }

    /** Couverture choisie, sinon première photo de l'album. */
    public function couvertureUrl(): ?string
    {
        return $this->couverture ?: $this->photos()->value('url');
    }
}
