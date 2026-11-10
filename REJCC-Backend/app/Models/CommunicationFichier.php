<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Document de marque de l'équipe Communication (fichier privé, jamais publié). */
class CommunicationFichier extends Model
{
    protected $table = 'communication_fichiers';

    protected $fillable = ['titre', 'description', 'categorie', 'fichier', 'fichier_nom', 'lien', 'mime', 'octets', 'telechargements', 'auteur_id'];

    protected $casts = ['octets' => 'integer', 'telechargements' => 'integer'];

    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }
}
