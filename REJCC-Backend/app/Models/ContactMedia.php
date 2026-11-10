<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Contact du fichier médias de la Communication (données personnelles). */
class ContactMedia extends Model
{
    protected $table = 'contacts_medias';

    protected $fillable = ['nom', 'media', 'type', 'fonction', 'email', 'telephone', 'ville', 'notes', 'dernier_contact', 'auteur_id'];

    protected $casts = ['dernier_contact' => 'date'];

    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }
}
