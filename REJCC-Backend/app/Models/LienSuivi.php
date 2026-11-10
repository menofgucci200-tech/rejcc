<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Lien de suivi de la Communication (rejcc.site/l/{code}) : redirige vers une
 * adresse enregistrée et compte les clics par jour, sans rien garder de la
 * personne qui clique.
 */
class LienSuivi extends Model
{
    protected $table = 'liens_suivi';

    /** Canaux de diffusion proposés à la Com. */
    public const CANAUX = [
        'whatsapp' => 'WhatsApp',
        'facebook' => 'Facebook',
        'linkedin' => 'LinkedIn',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'instagram' => 'Instagram',
        'email' => 'E-mail',
        'annonces' => 'Annonces',
        'autre' => 'Autre',
    ];

    protected $fillable = ['code', 'cible', 'libelle', 'canal', 'actif', 'cree_par'];

    protected $casts = ['actif' => 'boolean'];

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function clics(): HasMany
    {
        return $this->hasMany(LienSuiviClic::class, 'lien_id');
    }

    /** Ajoute un clic au compteur du jour (sûr si deux clics arrivent en même temps). */
    public function compterClic(): void
    {
        $jour = now()->toDateString();
        $q = fn () => LienSuiviClic::where('lien_id', $this->id)->where('jour', $jour);
        if ($q()->increment('clics') > 0) {
            return;
        }
        try {
            LienSuiviClic::create(['lien_id' => $this->id, 'jour' => $jour, 'clics' => 1]);
        } catch (UniqueConstraintViolationException) {
            $q()->increment('clics');
        }
    }
}
