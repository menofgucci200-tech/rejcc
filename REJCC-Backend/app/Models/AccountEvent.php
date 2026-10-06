<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Événement du journal d'un compte (visible par le membre). */
class AccountEvent extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'connexion' => 'Connexion',
        'deconnexion_appareil' => 'Appareil déconnecté',
        'mot_de_passe' => 'Mot de passe modifié',
        'mot_de_passe_reinitialise' => 'Mot de passe réinitialisé',
        'email_demande' => "Changement d'e-mail demandé",
        'email' => 'Adresse e-mail modifiée',
        'export' => 'Données téléchargées',
        'cloture' => 'Clôture du compte demandée',
        'cloture_annulee' => 'Clôture du compte annulée',
        'consultation_equipe' => "Document consulté par l'équipe",
        'notifications' => 'Réglages des notifications modifiés',
        'confidentialite' => 'Réglages de confidentialité modifiés',
    ];

    protected $fillable = ['user_id', 'type', 'detail', 'ip', 'agent', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
