<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Texte prêt à copier de la bibliothèque de la Communication. */
class CommunicationTexte extends Model
{
    protected $table = 'communication_textes';

    protected $fillable = ['cle', 'categorie', 'titre', 'contenu', 'limite', 'ordre', 'auteur_id'];

    protected $casts = ['limite' => 'integer', 'ordre' => 'integer'];
}
